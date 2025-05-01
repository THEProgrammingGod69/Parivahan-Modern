<?php
session_start();
require_once 'database/db_connect.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $vehicle_type = sanitize_input($_POST['vehicle_type']);
    $manufacturer = sanitize_input($_POST['manufacturer']);
    $model = sanitize_input($_POST['model']);
    $chassis_number = sanitize_input($_POST['chassis_number']);
    $engine_number = sanitize_input($_POST['engine_number']);
    $purchase_date = sanitize_input($_POST['purchase_date']);
    $dealer_name = sanitize_input($_POST['dealer_name']);
    $payment_amount = 0;
    
    // Set payment amount based on vehicle type
    switch ($vehicle_type) {
        case 'two_wheeler':
            $payment_amount = 1000.00;
            break;
        case 'four_wheeler':
            $payment_amount = 2500.00;
            break;
        case 'commercial':
            $payment_amount = 5000.00;
            break;
        case 'other':
            $payment_amount = 3000.00;
            break;
        default:
            $payment_amount = 0.00;
    }
    
    // Handle file uploads
    $insurance_details = "uploads/" . $user_id . "_insurance.pdf";
    $pollution_certificate = isset($_FILES['pollution_certificate']) ? "uploads/" . $user_id . "_pollution.pdf" : null;
    
    // Check if chassis number already exists
    $check_sql = "SELECT registration_id FROM vehicle_registrations WHERE chassis_number = ? OR engine_number = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $chassis_number, $engine_number);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "A vehicle with the same chassis number or engine number is already registered.";
    } else {
        // Insert application into database
        $sql = "INSERT INTO vehicle_registrations (user_id, vehicle_type, manufacturer, model, chassis_number, engine_number, purchase_date, dealer_name, insurance_details, pollution_certificate, payment_amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssssssd", $user_id, $vehicle_type, $manufacturer, $model, $chassis_number, $engine_number, $purchase_date, $dealer_name, $insurance_details, $pollution_certificate, $payment_amount);
        
        if ($stmt->execute()) {
            $registration_id = $stmt->insert_id;
            
            // Create notification for user
            $notification_title = "Vehicle Registration Application Submitted";
            $notification_message = "Your application for vehicle registration has been submitted successfully. Registration ID: #{$registration_id}";
            
            $notify_sql = "INSERT INTO notifications (user_id, notification_type, title, message) VALUES (?, 'application_update', ?, ?)";
            $notify_stmt = $conn->prepare($notify_sql);
            $notify_stmt->bind_param("iss", $user_id, $notification_title, $notification_message);
            $notify_stmt->execute();
            
            $success = "Your vehicle registration application has been submitted successfully. Registration ID: #{$registration_id}";
            // Redirect to payment page
            // header("Location: payment.php?type=registration&id=" . $registration_id);
        } else {
            $error = "Failed to submit application. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parivahan - Vehicle Registration</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info span {
            margin-right: 1rem;
        }

        .logout-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        nav {
            background-color: white;
            padding: 1rem 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav a {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        nav a:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
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

        select,
        input[type="text"],
        input[type="date"],
        input[type="file"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .vehicle-info {
            margin-top: 1.5rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid var(--primary-color);
        }

        .vehicle-info h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .vehicle-info p {
            margin-bottom: 0.5rem;
        }

        .vehicle-info ul {
            margin-left: 1.5rem;
            margin-bottom: 0.5rem;
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
            <a href="dashboard.php" class="logo">Parivahan Sewa</a>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <nav>
        <div class="nav-content">
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">My Profile</a>
            <a href="applications.php">My Applications</a>
            <a href="documents.php">My Documents</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </nav>

    <div class="container">
        <h2>Vehicle Registration Application</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label for="vehicle_type">Vehicle Type</label>
                <select id="vehicle_type" name="vehicle_type" required>
                    <option value="">Select Vehicle Type</option>
                    <option value="two_wheeler">Two Wheeler</option>
                    <option value="four_wheeler">Four Wheeler (Private)</option>
                    <option value="commercial">Commercial Vehicle</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="vehicle-info" id="two-wheeler-info" style="display: none;">
                <h3>Two Wheeler Registration Information</h3>
                <p><strong>Requirements:</strong></p>
                <ul>
                    <li>Valid ID Proof</li>
                    <li>Address Proof</li>
                    <li>Invoice from dealer</li>
                    <li>Insurance certificate</li>
                    <li>Pollution certificate (if applicable)</li>
                </ul>
                <p><strong>Fee:</strong> ₹1,000</p>
            </div>
            
            <div class="vehicle-info" id="four-wheeler-info" style="display: none;">
                <h3>Four Wheeler Registration Information</h3>
                <p><strong>Requirements:</strong></p>
                <ul>
                    <li>Valid ID Proof</li>
                    <li>Address Proof</li>
                    <li>Invoice from dealer</li>
                    <li>Insurance certificate</li>
                    <li>Pollution certificate</li>
                </ul>
                <p><strong>Fee:</strong> ₹2,500</p>
            </div>
            
            <div class="vehicle-info" id="commercial-info" style="display: none;">
                <h3>Commercial Vehicle Registration Information</h3>
                <p><strong>Requirements:</strong></p>
                <ul>
                    <li>Valid ID Proof</li>
                    <li>Address Proof</li>
                    <li>Invoice from dealer</li>
                    <li>Insurance certificate</li>
                    <li>Pollution certificate</li>
                    <li>Business registration documents</li>
                </ul>
                <p><strong>Fee:</strong> ₹5,000</p>
            </div>
            
            <div class="vehicle-info" id="other-info" style="display: none;">
                <h3>Other Vehicle Registration Information</h3>
                <p><strong>Requirements:</strong></p>
                <ul>
                    <li>Valid ID Proof</li>
                    <li>Address Proof</li>
                    <li>Invoice from dealer</li>
                    <li>Insurance certificate</li>
                    <li>Pollution certificate (if applicable)</li>
                    <li>Additional documentation may be required based on vehicle type</li>
                </ul>
                <p><strong>Fee:</strong> ₹3,000</p>
            </div>
            
            <div class="form-group">
                <label for="manufacturer">Manufacturer</label>
                <input type="text" id="manufacturer" name="manufacturer" required>
            </div>
            
            <div class="form-group">
                <label for="model">Model</label>
                <input type="text" id="model" name="model" required>
            </div>
            
            <div class="form-group">
                <label for="chassis_number">Chassis Number</label>
                <input type="text" id="chassis_number" name="chassis_number" required>
            </div>
            
            <div class="form-group">
                <label for="engine_number">Engine Number</label>
                <input type="text" id="engine_number" name="engine_number" required>
            </div>
            
            <div class="form-group">
                <label for="purchase_date">Purchase Date</label>
                <input type="date" id="purchase_date" name="purchase_date" required>
            </div>
            
            <div class="form-group">
                <label for="dealer_name">Dealer Name</label>
                <input type="text" id="dealer_name" name="dealer_name" required>
            </div>
            
            <div class="form-group">
                <label for="insurance_details">Insurance Certificate</label>
                <input type="file" id="insurance_details" name="insurance_details" accept="application/pdf" required>
            </div>
            
            <div class="form-group" id="pollution-certificate-group">
                <label for="pollution_certificate">Pollution Certificate (if applicable)</label>
                <input type="file" id="pollution_certificate" name="pollution_certificate" accept="application/pdf">
            </div>
            
            <button type="submit" class="btn">Submit Application</button>
        </form>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Parivahan Sewa. All rights reserved.</p>
        <p>Ministry of Road Transport & Highways, Government of India</p>
    </footer>

    <script>
        // Show/hide vehicle information based on selection
        document.getElementById('vehicle_type').addEventListener('change', function() {
            // Hide all info sections
            document.getElementById('two-wheeler-info').style.display = 'none';
            document.getElementById('four-wheeler-info').style.display = 'none';
            document.getElementById('commercial-info').style.display = 'none';
            document.getElementById('other-info').style.display = 'none';
            
            // Show selected info section
            var selectedType = this.value;
            if (selectedType === 'two_wheeler') {
                document.getElementById('two-wheeler-info').style.display = 'block';
            } else if (selectedType === 'four_wheeler') {
                document.getElementById('four-wheeler-info').style.display = 'block';
            } else if (selectedType === 'commercial') {
                document.getElementById('commercial-info').style.display = 'block';
            } else if (selectedType === 'other') {
                document.getElementById('other-info').style.display = 'block';
            }
        });
    </script>
</body>
</html>