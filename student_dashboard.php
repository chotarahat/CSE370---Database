<?php
session_start();
require_once("DBConnect.php");

// Check if user is student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get student info
$student_query = mysqli_query($conn, "SELECT * FROM student WHERE SID = '$student_id'");
$student = mysqli_fetch_assoc($student_query);
$student_area = $student['Address'];

// Parse student area to individual locations
$student_locations = explode('-', $student_area);

// Get buses that serve ANY of the student's locations
$available_buses = mysqli_query($conn, "
    SELECT DISTINCT b.* 
    FROM bus b 
    JOIN bus_routes br ON b.bus_number = br.bus_number 
    WHERE br.route_area IN ('" . implode("','", $student_locations) . "')
    ORDER BY b.bus_number
");

// Get all other buses that don't serve student's locations
$other_buses = mysqli_query($conn, "
    SELECT DISTINCT b.* 
    FROM bus b 
    JOIN bus_routes br ON b.bus_number = br.bus_number 
    WHERE br.route_area NOT IN ('" . implode("','", $student_locations) . "')
    ORDER BY b.bus_number
");

// Handle bus booking - Student only selects date and bus
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_bus'])) {
    $bus_number = mysqli_real_escape_string($conn, $_POST['bus_number']);
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    
    // Validate date is not in the past
    $today = date('Y-m-d');
    if ($booking_date < $today) {
        $error = "Cannot book for past dates!";
    } else {
        // Check if already booked for this date
        $check_booking = mysqli_query($conn, "
            SELECT * FROM bookings 
            WHERE student_id = '$student_id' 
            AND booking_date = '$booking_date'
        ");
        
        if (mysqli_num_rows($check_booking) > 0) {
            $error = "You already have a booking for this date!";
        } else {
            // Check if bus exists
            $check_bus = mysqli_query($conn, "SELECT * FROM bus WHERE bus_number = '$bus_number'");
            if (mysqli_num_rows($check_bus) == 0) {
                $error = "Selected bus does not exist!";
            } else {
                // Student only books with date and bus - payment method and seat type will be set by staff during boarding
                $sql = "INSERT INTO bookings (student_id, bus_number, booking_date) 
                        VALUES ('$student_id', '$bus_number', '$booking_date')";
                
                if (mysqli_query($conn, $sql)) {
                    $message = "Bus booked successfully! Note: Payment method and seat type will be confirmed by bus staff during boarding.";
                } else {
                    $error = "Error booking bus: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $feedback_text = mysqli_real_escape_string($conn, $_POST['feedback_text']);
    
    $sql = "INSERT INTO feedback (user_id, user_type, feedback_text) 
            VALUES ('$student_id', 'student', '$feedback_text')";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Feedback submitted successfully!";
    } else {
        $error = "Error submitting feedback: " . mysqli_error($conn);
    }
}

// Get payment history
$payment_history = mysqli_query($conn, "
    SELECT b.*, bu.bus_route 
    FROM bookings b
    JOIN bus bu ON b.bus_number = bu.bus_number
    WHERE b.student_id = '$student_id'
    ORDER BY b.booking_date DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
            background-color: #059669;
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
            color: #059669;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .student-info {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .info-card {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #059669;
        }
        
        .info-card h3 {
            color: #374151;
            margin-bottom: 5px;
        }
        
        .info-card p {
            color: #6b7280;
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
        
        input, select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .submit-btn {
            background-color: #059669;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .submit-btn:hover {
            background-color: #047857;
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
        
        .status-pending { color: #d97706; }
        .status-paid { color: #059669; }
        .status-half { color: #7c3aed; }
        
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
            border-bottom: 3px solid #059669;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .payment-method {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .cod { background-color: #fef3c7; color: #92400e; }
        .bkash { background-color: #dbeafe; color: #1e40af; }
        .notset { background-color: #f3f4f6; color: #6b7280; }
        
        .seat-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .sit { background-color: #dcfce7; color: #166534; }
        .stand { background-color: #fef3c7; color: #92400e; }
        .notset { background-color: #f3f4f6; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Dashboard - Welcome, <?php echo htmlspecialchars($student['Name']); ?></h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    
    <div class="container">
        <?php if($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="student-info">
            <div class="info-card">
                <h3>Student ID</h3>
                <p><?php echo htmlspecialchars($student['SID']); ?></p>
            </div>
            <div class="info-card">
                <h3>Area</h3>
                <p><?php echo htmlspecialchars($student['Address']); ?></p>
            </div>
            <div class="info-card">
                <h3>Department</h3>
                <p><?php echo htmlspecialchars($student['Department']); ?></p>
            </div>
            <div class="info-card">
                <h3>Phone</h3>
                <p><?php echo htmlspecialchars($student['Phone_Number']); ?></p>
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('book')">Book Bus</div>
            <div class="tab" onclick="showTab('history')">Booking History</div>
            <div class="tab" onclick="showTab('feedback')">Feedback</div>
        </div>
        
        <!-- Book Bus Tab -->
        <div id="book-tab" class="tab-content active">
            <div class="section">
                <h2>Book Your Bus</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Select Date *</label>
                            <?php $today = date('Y-m-d'); ?>
                            <input type="date" name="booking_date" required 
                                   min="<?php echo $today; ?>" 
                                   value="<?php echo $today; ?>">
                        </div>
                        <div class="form-group">
                            <label>Select Bus *</label>
                            <select name="bus_number" required>
                                <option value="">-- Select Bus --</option>
                                <?php if(mysqli_num_rows($available_buses) > 0): ?>
                                    <optgroup label="Your go to Buses (Your Area: <?php echo $student_area; ?>)">
                                        <?php mysqli_data_seek($available_buses, 0); ?>
                                        <?php while($bus = mysqli_fetch_assoc($available_buses)): ?>
                                            <option value="<?php echo htmlspecialchars($bus['bus_number']); ?>">
                                                <?php echo htmlspecialchars($bus['bus_number']); ?> - <?php echo htmlspecialchars($bus['bus_route']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </optgroup>
                                <?php endif; ?>
                                
                                <?php if(mysqli_num_rows($other_buses) > 0): ?>
                                    <optgroup label="Other Buses (Available)">
                                        <?php mysqli_data_seek($other_buses, 0); ?>
                                        <?php while($bus = mysqli_fetch_assoc($other_buses)): ?>
                                            <option value="<?php echo htmlspecialchars($bus['bus_number']); ?>">
                                                <?php echo htmlspecialchars($bus['bus_number']); ?> - <?php echo htmlspecialchars($bus['bus_route']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" name="book_bus" class="submit-btn">Book Bus</button>
                </form>
            </div>
        </div>
        
        <!-- Booking History Tab -->
        <div id="history-tab" class="tab-content">
            <div class="section">
                <h2>Booking History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Bus Number</th>
                            <th>Route</th>
                            <th>Payment Method</th>
                            <th>Seat Type</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($booking = mysqli_fetch_assoc($payment_history)): ?>
                            <?php
                            // Calculate amount based on seat type
                            $amount = 'Not Set';
                            if ($booking['seat_type'] == 'Sit') {
                                $amount = ($booking['payment_status'] == 'Half Paid') ? '50 TK' : '100 TK';
                            } elseif ($booking['seat_type'] == 'Stand') {
                                $amount = '50 TK';
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                                <td><?php echo htmlspecialchars($booking['bus_number']); ?></td>
                                <td><?php echo htmlspecialchars($booking['bus_route']); ?></td>
                                <td>
                                    <span class="payment-method <?php echo strtolower(str_replace(' ', '', $booking['payment_method'])); ?>">
                                        <?php echo $booking['payment_method']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="seat-type <?php echo strtolower($booking['seat_type']); ?>">
                                        <?php echo $booking['seat_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($booking['actual_amount'] > 0): ?>
                                        <?php echo $booking['actual_amount']; ?> TK
                                    <?php else: ?>
                                        <?php echo $amount; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="status-<?php echo strtolower(str_replace(' ', '', $booking['payment_status'])); ?>">
                                    <?php echo htmlspecialchars($booking['payment_status']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if(mysqli_num_rows($payment_history) == 0): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No bookings found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Feedback Tab -->
        <div id="feedback-tab" class="tab-content">
            <div class="section">
                <h2>Submit Feedback</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Your Feedback</label>
                        <textarea name="feedback_text" placeholder="Share your feedback to improve the system..." required></textarea>
                    </div>
                    <button type="submit" name="submit_feedback" class="submit-btn">Submit Feedback</button>
                </form>
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
