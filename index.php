<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTS Login</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        .nav {
            background-color: #1e40af;
            padding: 15px;
        }

        .nav h2 {
            color: white;
            margin: 0;
        }

        .login-container {
            max-width: 350px;
            margin: 80px auto;
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .login-container h3 {
            text-align: center;
            margin-bottom: 20px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #1e40af;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
        }

        button:hover {
            background-color: #c4be0fff;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="nav">
        <h2>Welcome to UTS</h2>
    </div>

    <div class="login-container">
        <h3>Login</h3>
        
        <?php if(isset($_GET['error'])): ?>
        <div class="error-message">
            Invalid login credentials!
        </div>
        <?php endif; ?>
        
        <form action="signIn.php" method="post">
            <select name="user_type" required>
                <option value="">Select User Type</option>
                <option value="student">Student</option>
                <option value="admin">Admin</option>
                <option value="bus_staff">Bus Staff</option>
            </select>
            
            <input name="sid" type="text" placeholder="User ID" required>
            <input name="pass" type="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>

</body>
</html>