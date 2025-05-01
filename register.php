<?php
session_start();
require_once 'database/db_connect.php';

$error = '';
$success = '';

// Check if user is already logged in
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

// Process registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize_input($_POST['full_name']);
    $phone_number = sanitize_input($_POST['phone_number']);
    $address = sanitize_input($_POST['address']);
    $aadhar_number = sanitize_input($_POST['aadhar_number']);
    $date_of_birth = sanitize_input($_POST['date_of_birth']);
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || 
        empty($full_name) || empty($phone_number) || empty($date_of_birth)) {
        $error = "All required fields must be filled";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (!empty($aadhar_number) && strlen($aadhar_number) !== 12) {
        $error = "Aadhar number must be 12 digits";
    } else {
        // Check if username already exists
        $check_username = "SELECT user_id FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_username);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already exists. Please choose another one.";
        } else {
            // Check if email already exists
            $check_email = "SELECT user_id FROM users WHERE email = ?";
            $stmt = $conn->prepare($check_email);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already registered. Please use another email or login.";
            } else {
                // Hash password
                $hashed_password = hash_password($password);
                
                // Insert new user
                $insert_sql = "INSERT INTO users (username, password, email, full_name, phone_number, address, aadhar_number, date_of_birth) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("ssssssss", $username, $hashed_password, $email, $full_name, $phone_number, $address, $aadhar_number, $date_of_birth);
                
                if ($stmt->execute()) {
                    $success = "Registration successful! You can now login.";
                    // Redirect to login page after 3 seconds
                    header("refresh:3;url=login.php");
                } else {
                    $error = "Registration failed: " . $conn->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parivahan - Register</title>
    <style>
        :root {
            --primary-color: #004080;
            --secondary-color: #0066cc;
            --accent-color: #ff6b6b;
            --success-color: #28a745;
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
            max-width: 800px;
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

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        .form-group {
            flex: 1;
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        input[type="date"],
        textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        .required::after {
            content: " *";
            color: var(--accent-color);
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
            padding: 0.5rem;
            background-color: rgba(255, 107, 107, 0.1);
            border-radius: 4px;
        }

        .success {
            color: var(--success-color);
            margin-bottom: 1rem;
            text-align: center;
            padding: 0.5rem;
            background-color: rgba(40, 167, 69, 0.1);
            border-radius: 4px;
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
        <h2>User Registration</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="username" class="required">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="required">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="required">Password</label>
                    <input type="password" id="password" name="password" required>
                    <small style="display: block; margin-top: 0.25rem; color: #666;">Minimum 8 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="required">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name" class="required">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone_number" class="required">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number" value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="date_of_birth" class="required">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="aadhar_number">Aadhar Number</label>
                    <input type="text" id="aadhar_number" name="aadhar_number" value="<?php echo isset($_POST['aadhar_number']) ? htmlspecialchars($_POST['aadhar_number']) : ''; ?>" maxlength="12">
                    <small style="display: block; margin-top: 0.25rem; color: #666;">12-digit number without spaces</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
            </div>
            
            <button type="submit" class="btn">Register</button>
        </form>
        
        <div class="links">
            <p>Already have an account? <a href="login.php">Login</a></p>
            <p><a href="Parivahan_home.html">Back to Home</a></p>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Parivahan Sewa. All rights reserved.</p>
    </footer>
</body>
</html>