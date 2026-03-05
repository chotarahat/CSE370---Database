<?php
require_once("DBConnect.php");

session_start();

if (isset($_POST['sid']) && isset($_POST['pass']) && isset($_POST['user_type'])) {
    $u = $_POST['sid'];
    $p = $_POST['pass'];
    $user_type = $_POST['user_type'];
    
    // Simple login check
    if ($user_type == 'student') {
        $sql = "SELECT * FROM student WHERE SID = '$u' AND password = '$p'";
    } 
    elseif ($user_type == 'admin') {
        $sql = "SELECT * FROM admin WHERE admin_id = '$u' AND password = '$p'";
    }
    elseif ($user_type == 'bus_staff') {
        $sql = "SELECT * FROM staff WHERE staff_id = '$u' AND password = '$p'";
    }
    
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
        echo($user_data);
        
        $_SESSION['user_id'] = $u;
        $_SESSION['user_type'] = $user_type;
        $_SESSION['user_name'] = $user_data['Name'] ?? $user_data['D_name'] ?? 'User';
        
        // Redirect
        if ($user_type == 'student') {
            header("Location: student_dashboard.php");
        } 
        elseif ($user_type == 'admin') {
            header("Location: admin_dashboard.php");
        }
        elseif ($user_type == 'bus_staff') {
            header("Location: staff_dashboard.php");
        }
        exit(); // for efficiency
    } else {
        header("Location: index.php?error=1");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>