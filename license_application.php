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
    $license_type = sanitize_input($_POST['license_type']);
    $payment_amount = 0;
    
    // Set payment amount based on license type
    switch ($license_type) {
        case 'learner':
            $payment_amount = 200.00;
            break;
        case 'permanent':
            $payment_amount = 500.00;
            break;
        case 'renewal':
            $payment_amount = 300.00;
            break;
        case 'international':
            $payment_amount = 1000.00;
            break;
        default:
            $payment_amount = 0.00;
    }
    
    // Handle file uploads
    $id_proof = null;
    $address_proof = null;
    $photo = null;
    $medical_certificate = null;
    
    // Process ID proof upload
    if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] == 0) {
        $file_name = $_FILES['id_proof']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($file_ext, $allowed_extensions)) {
            $new_file_name = $user_id . '_id_proof_' . time() . '.' . $file_ext;
            $id_proof = 'uploads/' . $new_file_name;
            
            move_uploaded_file($_FILES['id_proof']['tmp_name'], $id_proof);
        } else {
            $error = "Invalid file type for ID proof. Allowed types: JPG, JPEG, PNG, PDF.";
        }
    } else {
        $error = "Error uploading ID proof. Please try again.";
    }
    
    // Process address proof upload
    if (empty($error) && isset($_FILES['address_proof']) && $_FILES['address_proof']['error'] == 0) {
        $file_name = $_FILES['address_proof']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($file_ext, $allowed_extensions)) {
            $new_file_name = $user_id . '_address_proof_' . time() . '.' . $file_ext;
            $address_proof = 'uploads/' . $new_file_name;
            
            move_uploaded_file($_FILES['address_proof']['tmp_name'], $address_proof);
        } else {
            $error = "Invalid file type for address proof. Allowed types: JPG, JPEG, PNG, PDF.";
        }
    } else if (empty($error)) {
        $error = "Error uploading address proof. Please try again.";
    }
    
    // Process photo upload
    if (empty($error) && isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $file_name = $_FILES['photo']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        
        if (in_array($file_ext, $allowed_extensions)) {
            $new_file_name = $user_id . '_photo_' . time() . '.' . $file_ext;
            $photo = 'uploads/' . $new_file_name;
            
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
        } else {
            $error = "Invalid file type for photo. Allowed types: JPG, JPEG, PNG.";
        }
    } else if (empty($error)) {
        $error = "Error uploading photo. Please try again.";
    }
    
    // Process medical certificate upload if required
    if (empty($error) && ($license_type == 'international' || $license_type == 'renewal')) {
        if (isset($_FILES['medical_certificate']) && $_FILES['medical_certificate']['error'] == 0) {
            $file_name = $_FILES['medical_certificate']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf'];
            
            if (in_array($file_ext, $allowed_extensions)) {
                $new_file_name = $user_id . '_medical_' . time() . '.' . $file_ext;
                $medical_certificate = 'uploads/' . $new_file_name;
                
                move_uploaded_file($_FILES['medical_certificate']['tmp_name'], $medical_certificate);
            } else {
                $error = "Invalid file type for medical certificate. Allowed type: PDF.";
            }
        } else {
            $error = "Medical certificate is required for this license type.";
        }
    }
    
    // Insert application into database
    $sql = "INSERT INTO license_applications (user_id, license_type, id_proof, address_proof, photo, medical_certificate, payment_amount) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssd", $user_id, $license_type, $id_proof, $address_proof, $photo, $medical_certificate, $payment_amount);
    
    if ($stmt->execute()) {
        $application_id = $stmt->insert_id;
        
        // Create notification for user
        $notification_title = "License Application Submitted";
        $notification_message = "Your application for {$license_type} license has been submitted successfully. Application ID: #{$application_id}";
        
        $notify_sql = "INSERT INTO notifications (user_id, notification_type, title, message) VALUES (?, 'application_update', ?, ?)";
        $notify_stmt = $conn->prepare($notify_sql);
        $notify_stmt->bind_param("iss", $user_id, $notification_title, $notification_message);
        $notify_stmt->execute();
        
        $success = "Your license application has been submitted successfully. Application ID: #{$application_id}";
        // Redirect to payment page
        header("Location: payment.php?type=license&id=" . $application_id);
        exit();
    } else {
        $error = "Failed to submit application. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parivahan - License Application</title>
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
        input[type="file"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .license-info {
            margin-top: 1.5rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid var(--primary-color);
        }

        .license-info h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .license-info p {
            margin-bottom: 0.5rem;
        }

        .license-info ul {
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
        <h2>License Application</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label for="license_type">License Type</label>
                <select id="license_type" name="license_type" required>
                    <option value="">Select License Type</option>
                    <option value="learner">Learner's License</option>
                    <option value="permanent">Permanent License</option>
                    <option value="renewal">License Renewal</option>
                    <option value="international">International Driving Permit</option>
                </select>
            </div>
            
            <div class="license-info" id="learner-info" style="display: none;">
                <h3>Learner's License Information</h3>
                <p>A learner's license is valid for 6 months and allows you to practice driving under supervision.</p>
                <p><strong>Requirements:</strong></p>
                <ul>
                    <li>Age: Minimum 16 years for two-wheelers, 18 years for light motor vehicles</li>
                    <li>ID Proof: Aadhar Card, Passport, Voter ID, etc.</li>
                    <li>Address Proof: Utility Bill, Rental Agreement, etc.</li>
                    <li>Recent passport-sized photograph</li>
                </ul>
                <p><strong>Fee:</strong> ₹200</p>
            </div>
            
            <div class="license-info" id="permanent-info" style="display: none;">
                <h3>Permanent License Information</h3>
                <p>A permanent license is issued after you pass the driving test and is valid for 20 years or until the age of 50.</p>
                <p><strong>Requirements:</strong></p>
                <ul>
                    <li>Valid Learner's License (held for at least 30 days)</li>
                    <li>ID Proof: Aadhar Card, Passport, Voter ID, etc.</li>
                    <li>Address Proof: Utility Bill, Rental Agreement, etc.</li>
                    <li>Recent passport-sized photograph</li>
                    <li>Pass the driving test</li>
                </ul>
                <p><strong>Fee:</strong> ₹500</p>
            </div>
            
            <div class="license-info" id="renewal-info" style="display: none;">
                <h3>License Renewal Information</h3>
                <p>You can renew your driving license before its expiry or within 5 years after expiry.</p>
                <p><strong>Requirements:</strong></p>
                <ul>
                    <li>Original Driving License</li>
                    <li>ID Proof: Aadhar Card, Passport, Voter ID, etc.</li>
                    <li>Address Proof: Utility Bill, Rental Agreement, etc.</li>
                    <li>Recent passport-sized photograph</li>
                    <li>Medical certificate (for applicants above 40 years)</li>
                </ul>
                <p><strong>Fee:</strong> ₹300</p>
            </div>
            
            <div class="license-info" id="international-info" style="display: none;">
                <h3>International Driving Permit Information</h3>
                <p>An International Driving Permit allows you to drive in foreign countries and is valid for 1 year.</p>
                <p><strong>Requirements:</strong></p>
                <ul>
                    <li>Valid Indian Driving License</li>
                    <li>Passport with valid visa</li>
                    <li>ID Proof: Aadhar Card, Passport, Voter ID, etc.</li>
                    <li>Recent passport-sized photograph</li>
                    <li>Medical certificate</li>
                </ul>
                <p><strong>Fee:</strong> ₹1000</p>
            </div>
            
            <div class="form-group">
                <label for="id_proof">ID Proof</label>
                <input type="file" id="id_proof" name="id_proof" accept="image/jpeg,image/png,application/pdf" required>
            </div>
            
            <div class="form-group">
                <label for="address_proof">Address Proof</label>
                <input type="file" id="address_proof" name="address_proof" accept="image/jpeg,image/png,application/pdf" required>
            </div>
            
            <div class="form-group">
                <label for="photo">Photograph</label>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png" required>
            </div>
            
            <div class="form-group" id="medical-certificate-group" style="display: none;">
                <label for="medical_certificate">Medical Certificate</label>
                <input type="file" id="medical_certificate" name="medical_certificate" accept="application/pdf">
            </div>
            
            <button type="submit" class="btn">Submit Application</button>
        </form>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Parivahan Sewa. All rights reserved.</p>
        <p>Ministry of Road Transport & Highways, Government of India</p>
    </footer>

    <script>
        // Show/hide license information based on selection
        document.getElementById('license_type').addEventListener('change', function() {
            // Hide all info sections
            document.getElementById('learner-info').style.display = 'none';
            document.getElementById('permanent-info').style.display = 'none';
            document.getElementById('renewal-info').style.display = 'none';
            document.getElementById('international-info').style.display = 'none';
            document.getElementById('medical-certificate-group').style.display = 'none';
            
            // Show selected info section
            var selectedType = this.value;
            if (selectedType) {
                document.getElementById(selectedType + '-info').style.display = 'block';
                
                // Show medical certificate field for renewal and international
                if (selectedType === 'renewal' || selectedType === 'international') {
                    document.getElementById('medical-certificate-group').style.display = 'block';
                }
            }
        });
    </script>
</body>
</html>