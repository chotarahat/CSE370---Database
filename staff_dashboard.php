<?php
session_start();
require_once("DBConnect.php");

// Check if user is staff
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'bus_staff') {
    header("Location: index.php");
    exit();
}

$staff_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get staff info and assigned bus
$staff_query = mysqli_query($conn, "SELECT * FROM staff WHERE staff_id = '$staff_id'");
$staff = mysqli_fetch_assoc($staff_query);
$assigned_bus = $staff['Assigned_Bus'];

// Handle date selection
$selected_date = date('Y-m-d');
if (isset($_GET['date'])) {
    $selected_date = mysqli_real_escape_string($conn, $_GET['date']);
}

// Handle student details update during boarding
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_student'])) {
        $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $seat_type = mysqli_real_escape_string($conn, $_POST['seat_type']);
        $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
        
        // Calculate amount based on seat type
        $amount = ($seat_type == 'Stand') ? 50.00 : 100.00;
        if ($payment_status == 'Half Paid' && $seat_type == 'Sit') {
            $amount = 50.00; // Half payment for sit
        }
        
        $sql = "UPDATE bookings SET 
                payment_method = '$payment_method',
                seat_type = '$seat_type',
                payment_status = '$payment_status',
                actual_amount = '$amount'
                WHERE booking_id = '$booking_id'";
        
        if (mysqli_query($conn, $sql)) {
            $message = "Student details updated successfully!";
        } else {
            $error = "Error updating student details: " . mysqli_error($conn);
        }
    }
}

// Get bookings for selected date and assigned bus
$bookings = mysqli_query($conn, "
    SELECT b.*, s.Name as student_name, s.SID, s.Phone_Number
    FROM bookings b
    JOIN student s ON b.student_id = s.SID
    WHERE b.booking_date = '$selected_date' 
    AND b.bus_number = '$assigned_bus'
    ORDER BY b.booked_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
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
            background-color: #7c3aed;
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
            color: #7c3aed;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .staff-info {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .info-card {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #7c3aed;
        }
        
        .info-card h3 {
            color: #374151;
            margin-bottom: 5px;
        }
        
        .info-card p {
            color: #6b7280;
        }
        
        .date-selector {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .date-selector input {
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
        }
        
        .go-btn {
            background-color: #7c3aed;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .go-btn:hover {
            background-color: #6d28d9;
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
        
        .update-form {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            min-width: 100px;
        }
        
        .update-btn {
            background-color: #10b981;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .update-btn:hover {
            background-color: #059669;
        }
        
        .student-id {
            font-weight: bold;
            color: #1e40af;
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
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .notpaid { background-color: #fee2e2; color: #991b1b; }
        .paid { background-color: #dcfce7; color: #166534; }
        .half { background-color: #fef3c7; color: #92400e; }
    
        .capacity-info {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #7c3aed;
        }
        
        .capacity-info h3 {
            color: #374151;
            margin-bottom: 10px;
        }
        
        .capacity-stats {
            display: flex;
            gap: 20px;
        }
        
        .capacity-stat {
            text-align: center;
            padding: 10px;
            background-color: white;
            border-radius: 6px;
            flex: 1;
            border: 1px solid #e5e7eb;
        }
        
        .capacity-value {
            font-size: 24px;
            font-weight: bold;
            color: #7c3aed;
        }
        
        .capacity-label {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Staff Dashboard - Welcome, <?php echo htmlspecialchars($staff['D_name']); ?></h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    
    <div class="container">
        <?php if($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="staff-info">
            <div class="info-card">
                <h3>Staff ID</h3>
                <p><?php echo htmlspecialchars($staff['staff_id']); ?></p>
            </div>
            <div class="info-card">
                <h3>Assigned Bus</h3>
                <p><?php echo $assigned_bus ? htmlspecialchars($assigned_bus) : 'Not Assigned'; ?></p>
            </div>
            <div class="info-card">
                <h3>Phone</h3>
                <p><?php echo htmlspecialchars($staff['Staff_PhoneNumber']); ?></p>
            </div>
        </div>
        
        <div class="section">
            <h2>Bookings for <?php echo date('F j, Y', strtotime($selected_date)); ?></h2>
            
            <?php if(!$assigned_bus): ?>
                <div class="error">You are not assigned to any bus. Please contact admin.</div>
            <?php else: ?>
                <div class="date-selector">
                    <form method="GET" action="">
                        <label>Select Date:</label>
                        <input type="date" name="date" value="<?php echo $selected_date; ?>">
                        <button type="submit" class="go-btn">Go</button>
                    </form>
                </div>
                
                <?php
                // Calculate summary
                $total_bookings = mysqli_num_rows($bookings);
                $bus_capacity = 20; // Fixed capacity for all buses
                mysqli_data_seek($bookings, 0);
                ?>
                
                <div class="capacity-info">
                    <h3>Capacity Summary for Bus <?php echo $assigned_bus; ?></h3>
                    <div class="capacity-stats">
                        <div class="capacity-stat">
                            <div class="capacity-value"><?php echo $total_bookings; ?></div>
                            <div class="capacity-label">Students Boarded</div>
                        </div>
                        <div class="capacity-stat">
                            <div class="capacity-value"><?php echo $bus_capacity; ?></div>
                            <div class="capacity-label">Total Capacity</div>
                        </div>
                        <div class="capacity-stat">
                            <div class="capacity-value"><?php echo $bus_capacity - $total_bookings; ?></div>
                            <div class="capacity-label">Seats Available</div>
                        </div>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Payment Method</th>
                            <th>Seat Type</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                            <th>Update Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($booking = mysqli_fetch_assoc($bookings)): ?>
                        <tr>
                            <td class="student-id"><?php echo htmlspecialchars($booking['SID']); ?></td>
                            <td><?php echo htmlspecialchars($booking['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['Phone_Number']); ?></td>
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
                                    Not Set
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo strtolower(str_replace(' ', '', $booking['payment_status'])); ?>">
                                    <?php echo $booking['payment_status']; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="update-form">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                    
                                    <select name="payment_method" required>
                                        <option value="Not Set" <?php echo $booking['payment_method'] == 'Not Set' ? 'selected' : ''; ?>>Payment Method</option>
                                        <option value="COD" <?php echo $booking['payment_method'] == 'COD' ? 'selected' : ''; ?>>COD (Cash)</option>
                                        <option value="Bkash" <?php echo $booking['payment_method'] == 'Bkash' ? 'selected' : ''; ?>>Bkash</option>
                                    </select>
                                    
                                    <select name="seat_type" required>
                                        <option value="Not Set" <?php echo $booking['seat_type'] == 'Not Set' ? 'selected' : ''; ?>>Seat Type</option>
                                        <option value="Sit" <?php echo $booking['seat_type'] == 'Sit' ? 'selected' : ''; ?>>Sit (100 TK)</option>
                                        <option value="Stand" <?php echo $booking['seat_type'] == 'Stand' ? 'selected' : ''; ?>>Stand (50 TK)</option>
                                    </select>
                                    
                                    <select name="payment_status" required>
                                        <option value="Not Paid" <?php echo $booking['payment_status'] == 'Not Paid' ? 'selected' : ''; ?>>Not Paid</option>
                                        <option value="Paid" <?php echo $booking['payment_status'] == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Half Paid" <?php echo $booking['payment_status'] == 'Half Paid' ? 'selected' : ''; ?>>Half Paid</option>
                                    </select>
                                    
                                    <button type="submit" name="update_student" class="update-btn">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($total_bookings == 0): ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No bookings found for this date</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
