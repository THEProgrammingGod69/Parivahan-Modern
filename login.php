<?php
session_start();
require_once 'database/db_connect.php';

$error = '';

// Check if user is already logged in
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Check user credentials
        $sql = "SELECT user_id, username, password, full_name FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (verify_password($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Update last login time
                $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $user['user_id']);
                $update_stmt->execute();
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parivahan - Login</title>
    <style>
        :root {
            --primary-color: #004080;
            --secondary-color: #0066cc;
            --accent-color: #ff6b6b;
            --background-light: #f8f9fa;
            --text-dark: #343a40;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background-color: var(--background-light);
        }

        header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 0;
            box-shadow: var(--shadow);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            text-align: center;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .container {
            max-width: 500px;
            margin: 3rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        .btn:hover {
            background-color: var(--secondary-color);
        }

        .error {
            color: var(--accent-color);
            margin-bottom: 1rem;
            text-align: center;
        }

        .links {
            margin-top: 1.5rem;
            text-align: center;
        }

        .links a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .links a:hover {
            text-decoration: underline;
        }

        footer {
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 0;
            margin-top: 4rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">Parivahan Sewa</div>
            <p>Transport Services Portal of India</p>
        </div>
    </header>

    <div class="container">
        <h2>User Login</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="links">
            <p>Don't have an account? <a href="register.php">Register</a></p>
            <p><a href="forgot_password.php">Forgot Password?</a></p>
            <p><a href="Parivahan_home.html">Back to Home</a></p>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Parivahan Sewa. All rights reserved.</p>
    </footer>
</body>
</html>