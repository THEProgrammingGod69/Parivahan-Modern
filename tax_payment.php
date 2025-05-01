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
$vehicles = [];

// Get user's registered vehicles
$vehicle_sql = "SELECT * FROM vehicle_registrations WHERE user_id = ? AND status = 'approved' ORDER BY registration_date DESC";
$vehicle_stmt = $conn->prepare($vehicle_sql);
$vehicle_stmt->bind_param("i", $user_id);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();

while ($row = $vehicle_result->fetch_assoc()) {
    $vehicles[] = $row;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $vehicle_id = sanitize_input($_POST['vehicle_id']);
    $tax_period = sanitize_input($_POST['tax_period']);
    $tax_amount = 0;
    
    // Get vehicle details
    $vehicle_sql = "SELECT * FROM vehicle_registrations WHERE registration_id = ? AND user_id = ?";
    $vehicle_stmt = $conn->prepare($vehicle_sql);
    $vehicle_stmt->bind_param("ii", $vehicle_id, $user_id);
    $vehicle_stmt->execute();
    $vehicle_result = $vehicle_stmt->get_result();
    
    if ($vehicle_result->num_rows === 1) {
        $vehicle = $vehicle_result->fetch_assoc();
        
        // Calculate tax amount based on vehicle type and period
        switch ($vehicle['vehicle_type']) {
            case 'two_wheeler':
                $tax_amount = ($tax_period == '1') ? 800.00 : 2000.00;
                break;
            case 'four_wheeler':
                $tax_amount = ($tax_period == '1') ? 2000.00 : 5000.00;
                break;
            case 'commercial':
                $tax_amount = ($tax_period == '1') ? 4000.00 : 10000.00;
                break;
            case 'other':
                $tax_amount = ($tax_period == '1') ? 1500.00 : 3500.00;
                break;
            default:
                $tax_amount = 0.00;
        }
        
        // Calculate tax period dates
        $tax_period_start = date('Y-m-d');
        $tax_period_end = ($tax_period == '1') ? date('Y-m-d', strtotime('+1 year')) : date('Y-m-d', strtotime('+3 years'));
        
        // Insert tax payment record
        $sql = "INSERT INTO road_tax_payments (vehicle_registration_id, user_id, tax_amount, tax_period_start, tax_period_end) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iidss", $vehicle_id, $user_id, $tax_amount, $tax_period_start, $tax_period_end);
        
        if ($stmt->execute()) {
            $tax_payment_id = $stmt->insert_id;
            
            // Create notification for user
            $notification_title = "Road Tax Payment Initiated";
            $notification_message = "Your road tax payment for {$vehicle['manufacturer']} {$vehicle['model']} ({$vehicle['registration_number']}) has been initiated. Payment ID: #{$tax_payment_id}";
            
            $notify_sql = "INSERT INTO notifications (user_id, notification_type, title, message) VALUES (?, 'payment', ?, ?)";
            $notify_stmt = $conn->prepare($notify_sql);
            $notify_stmt->bind_param("iss", $user_id, $notification_title, $notification_message);
            $notify_stmt->execute();
            
            $success = "Your road tax payment has been initiated successfully. Payment ID: #{$tax_payment_id}";
            // Redirect to payment page
            header("Location: payment.php?type=tax&id=" . $tax_payment_id);
            exit();
        } else {
            $error = "Failed to initiate tax payment. Please try again.";
        }
    } else {
        $error = "Invalid vehicle selected.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parivahan - Road Tax Payment</title>
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

        select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .tax-info {
            margin-top: 1.5rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid var(--primary-color);
        }

        .tax-info h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .tax-info p {
            margin-bottom: 0.5rem;
        }

        .tax-info ul {
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

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state p {
            margin-bottom: 1.5rem;
        }

        .vehicle-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .vehicle-card:hover {
            border-color: var(--primary-color);
            background-color: rgba(0, 64, 128, 0.05);
        }

        .vehicle-card.selected {
            border-color: var(--primary-color);
            background-color: rgba(0, 64, 128, 0.1);
        }

        .vehicle-card h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .vehicle-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .detail-label {
            font-weight: 500;
            color: #555;
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
        <h2>Road Tax Payment</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (empty($vehicles)): ?>
            <div class="empty-state">
                <p>You don't have any registered vehicles. Please register a vehicle first to pay road tax.</p>
                <a href="vehicle_registration.php" class="btn">Register Vehicle</a>
            </div>
        <?php else: ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label>Select Vehicle</label>
                    <?php foreach ($vehicles as $index => $vehicle): ?>
                        <div class="vehicle-card" onclick="selectVehicle(<?php echo $vehicle['registration_id']; ?>, this)">
                            <h3><?php echo htmlspecialchars($vehicle['manufacturer'] . ' ' . $vehicle['model']); ?></h3>
                            <div class="vehicle-details">
                                <div>
                                    <span class="detail-label">Registration:</span>
                                    <?php echo htmlspecialchars($vehicle['registration_number']); ?>
                                </div>
                                <div>
                                    <span class="detail-label">Type:</span>
                                    <?php echo ucfirst(str_replace('_', ' ', $vehicle['vehicle_type'])); ?>
                                </div>
                                <div>
                                    <span class="detail-label">Registered On:</span>
                                    <?php echo date('d M Y', strtotime($vehicle['registration_date'])); ?>
                                </div>
                                <div>
                                    <span class="detail-label">Expires On:</span>
                                    <?php echo date('d M Y', strtotime($vehicle['registration_expiry'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <input type="hidden" id="vehicle_id" name="vehicle_id" required>
                </div>
                
                <div class="form-group">
                    <label for="tax_period">Tax Period</label>
                    <select id="tax_period" name="tax_period" required>
                        <option value="">Select Tax Period</option>
                        <option value="1">1 Year</option>
                        <option value="3">3 Years</option>
                    </select>
                </div>
                
                <div class="tax-info" id="tax-info" style="display: none;">
                    <h3>Road Tax Information</h3>
                    <p id="tax-vehicle-info"></p>
                    <p><strong>Tax Period:</strong> <span id="tax-period-text"></span></p>
                    <p><strong>Tax Amount:</strong> <span id="tax-amount"></span></p>
                    <p><strong>Note:</strong> Road tax is mandatory for all registered vehicles and must be paid before the due date to avoid penalties.</p>
                </div>
                
                <button type="submit" class="btn" id="submit-btn" disabled>Proceed to Payment</button>
            </form>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Parivahan Sewa. All rights reserved.</p>
        <p>Ministry of Road Transport & Highways, Government of India</p>
    </footer>

    <script>
        // Vehicle selection
        function selectVehicle(vehicleId, element) {
            // Reset all vehicle cards
            document.querySelectorAll('.vehicle-card').forEach(function(card) {
                card.classList.remove('selected');
            });
            
            // Select current vehicle card
            element.classList.add('selected');
            
            // Set vehicle ID
            document.getElementById('vehicle_id').value = vehicleId;
            
            // Update tax info
            updateTaxInfo(element.querySelector('h3').textContent, element.querySelector('.vehicle-details').textContent);
            
            // Enable submit button if tax period is selected
            checkFormValidity();
        }
        
        // Tax period selection
        document.getElementById('tax_period').addEventListener('change', function() {
            // Update tax info
            if (document.getElementById('vehicle_id').value) {
                const selectedVehicle = document.querySelector('.vehicle-card.selected');
                updateTaxInfo(selectedVehicle.querySelector('h3').textContent, selectedVehicle.querySelector('.vehicle-details').textContent);
            }
            
            // Enable submit button if vehicle is selected
            checkFormValidity();
        });
        
        // Update tax information
        function updateTaxInfo(vehicleName, vehicleDetails) {
            const taxPeriod = document.getElementById('tax_period').value;
            if (!taxPeriod) return;
            
            // Extract vehicle type from details
            const vehicleTypeMatch = vehicleDetails.match(/Type:\s*([^\n]+)/);
            const vehicleType = vehicleTypeMatch ? vehicleTypeMatch[1].trim().toLowerCase() : '';
            
            // Calculate tax amount based on vehicle type and period
            let taxAmount = 0;
            if (vehicleType.includes('two wheeler')) {
                taxAmount = (taxPeriod == '1') ? 800 : 2000;
            } else if (vehicleType.includes('four wheeler')) {
                taxAmount = (taxPeriod == '1') ? 2000 : 5000;
            } else if (vehicleType.includes('commercial')) {
                taxAmount = (taxPeriod == '1') ? 4000 : 10000;
            } else {
                taxAmount = (taxPeriod == '1') ? 1500 : 3500;
            }
            
            // Update tax info
            document.getElementById('tax-vehicle-info').textContent = 'Vehicle: ' + vehicleName;
            document.getElementById('tax-period-text').textContent = taxPeriod + ' Year' + (taxPeriod > 1 ? 's' : '');
            document.getElementById('tax-amount').textContent = '₹' + taxAmount.toLocaleString();
            
            // Show tax info
            document.getElementById('tax-info').style.display = 'block';
        }
        
        // Check form validity
        function checkFormValidity() {
            const vehicleId = document.getElementById('vehicle_id').value;
            const taxPeriod = document.getElementById('tax_period').value;
            
            document.getElementById('submit-btn').disabled = !(vehicleId && taxPeriod);
        }
    </script>
</body>
</html>