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

// Check if payment type and ID are provided
if (!isset($_GET['type']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$payment_type = sanitize_input($_GET['type']);
$reference_id = intval($_GET['id']);
$payment_details = [];

// Get payment details based on type
if ($payment_type === 'license') {
    $sql = "SELECT * FROM license_applications WHERE application_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $reference_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $payment_details = $result->fetch_assoc();
        $payment_details['service_name'] = 'License Application - ' . ucfirst($payment_details['license_type']);
    } else {
        $error = "Invalid license application reference.";
    }
} elseif ($payment_type === 'registration') {
    $sql = "SELECT * FROM vehicle_registrations WHERE registration_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $reference_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $payment_details = $result->fetch_assoc();
        $payment_details['service_name'] = 'Vehicle Registration';
    } else {
        $error = "Invalid vehicle registration reference.";
    }
} elseif ($payment_type === 'tax') {
    $sql = "SELECT * FROM road_tax_payments WHERE tax_payment_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $reference_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $payment_details = $result->fetch_assoc();
        $payment_details['service_name'] = 'Road Tax Payment';
    } else {
        $error = "Invalid tax payment reference.";
    }
} else {
    $error = "Invalid payment type.";
}

// Process payment form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error)) {
    $payment_method = sanitize_input($_POST['payment_method']);
    $amount = floatval($payment_details['payment_amount']);
    $transaction_reference = 'TXN' . time() . rand(1000, 9999);
    
    // Insert payment transaction
    $transaction_sql = "INSERT INTO payment_transactions (user_id, service_type, reference_id, amount, payment_method, transaction_reference, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'completed')";
    $transaction_stmt = $conn->prepare($transaction_sql);
    $transaction_stmt->bind_param("isiiss", $user_id, $payment_type, $reference_id, $amount, $payment_method, $transaction_reference);
    
    if ($transaction_stmt->execute()) {
        $transaction_id = $transaction_stmt->insert_id;
        
        // Update payment status in the respective application table
        if ($payment_type === 'license') {
            $update_sql = "UPDATE license_applications SET payment_status = 'completed', payment_date = NOW(), payment_reference = ? WHERE application_id = ?";
        } elseif ($payment_type === 'registration') {
            $update_sql = "UPDATE vehicle_registrations SET payment_status = 'completed', payment_date = NOW(), payment_reference = ? WHERE registration_id = ?";
        } elseif ($payment_type === 'tax') {
            $update_sql = "UPDATE road_tax_payments SET payment_status = 'completed', payment_reference = ? WHERE tax_payment_id = ?";
        }
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $transaction_reference, $reference_id);
        $update_stmt->execute();
        
        // Create notification for user
        $notification_title = "Payment Successful";
        $notification_message = "Your payment of ₹{$amount} for {$payment_details['service_name']} has been processed successfully. Transaction ID: {$transaction_reference}";
        
        $notify_sql = "INSERT INTO notifications (user_id, notification_type, title, message) VALUES (?, 'payment', ?, ?)";
        $notify_stmt = $conn->prepare($notify_sql);
        $notify_stmt->bind_param("iss", $user_id, $notification_title, $notification_message);
        $notify_stmt->execute();
        
        $success = "Payment successful! Transaction ID: {$transaction_reference}";
    } else {
        $error = "Payment processing failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parivahan - Payment</title>
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

        .payment-details {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .payment-details h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .payment-details p {
            margin-bottom: 0.5rem;
        }

        .payment-details .amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 1rem 0;
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
        input[type="number"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .card-details {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
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

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-method {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: var(--primary-color);
            background-color: rgba(0, 64, 128, 0.05);
        }

        .payment-method.selected {
            border-color: var(--primary-color);
            background-color: rgba(0, 64, 128, 0.1);
        }

        .payment-method img {
            height: 40px;
            margin-bottom: 0.5rem;
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
        <h2>Payment</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
            <p style="text-align: center; margin-top: 1rem;">
                <a href="dashboard.php" class="btn">Return to Dashboard</a>
            </p>
        <?php elseif (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
            <div class="payment-details">
                <h3>Payment Receipt</h3>
                <p><strong>Service:</strong> <?php echo htmlspecialchars($payment_details['service_name']); ?></p>
                <p><strong>Amount Paid:</strong> ₹<?php echo number_format($payment_details['payment_amount'], 2); ?></p>
                <p><strong>Transaction ID:</strong> <?php echo $transaction_reference; ?></p>
                <p><strong>Date:</strong> <?php echo date('d M Y, h:i A'); ?></p>
                <p><strong>Status:</strong> <span style="color: var(--success-color);">Completed</span></p>
            </div>
            <p style="text-align: center;">
                <a href="dashboard.php" class="btn">Return to Dashboard</a>
            </p>
        <?php else: ?>
            <div class="payment-details">
                <h3><?php echo htmlspecialchars($payment_details['service_name']); ?></h3>
                <p><strong>Reference ID:</strong> #<?php echo $reference_id; ?></p>
                <?php if ($payment_type === 'license'): ?>
                    <p><strong>License Type:</strong> <?php echo ucfirst($payment_details['license_type']); ?></p>
                <?php elseif ($payment_type === 'registration'): ?>
                    <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($payment_details['manufacturer'] . ' ' . $payment_details['model']); ?></p>
                <?php endif; ?>
                <p class="amount">Amount: ₹<?php echo number_format($payment_details['payment_amount'], 2); ?></p>
            </div>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?type=' . $payment_type . '&id=' . $reference_id; ?>">
                <h3>Select Payment Method</h3>
                
                <div class="payment-methods">
                    <div class="payment-method" onclick="selectPaymentMethod('credit_card')">
                        <img src="https://via.placeholder.com/150x50?text=Credit+Card" alt="Credit Card">
                        <p>Credit Card</p>
                    </div>
                    <div class="payment-method" onclick="selectPaymentMethod('debit_card')">
                        <img src="https://via.placeholder.com/150x50?text=Debit+Card" alt="Debit Card">
                        <p>Debit Card</p>
                    </div>
                    <div class="payment-method" onclick="selectPaymentMethod('net_banking')">
                        <img src="https://via.placeholder.com/150x50?text=Net+Banking" alt="Net Banking">
                        <p>Net Banking</p>
                    </div>
                    <div class="payment-method" onclick="selectPaymentMethod('upi')">
                        <img src="https://via.placeholder.com/150x50?text=UPI" alt="UPI">
                        <p>UPI</p>
                    </div>
                    <div class="payment-method" onclick="selectPaymentMethod('wallet')">
                        <img src="https://via.placeholder.com/150x50?text=Wallet" alt="Wallet">
                        <p>Wallet</p>
                    </div>
                    <div class="payment-method" onclick="selectPaymentMethod('other')">
                        <img src="https://via.placeholder.com/150x50?text=Other" alt="Other">
                        <p>Other</p>
                    </div>
                </div>
                
                <input type="hidden" id="payment_method" name="payment_method" value="">
                
                <div id="credit-card-details" style="display: none;">
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" placeholder="1234 5678 9012 3456">
                    </div>
                    
                    <div class="card-details">
                        <div class="form-group">
                            <label for="card_name">Name on Card</label>
                            <input type="text" id="card_name" placeholder="John Doe">
                        </div>
                        <div class="form-group">
                            <label for="expiry">Expiry Date</label>
                            <input type="text" id="expiry" placeholder="MM/YY">
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" placeholder="123">
                        </div>
                    </div>
                </div>
                
                <div id="upi-details" style="display: none;">
                    <div class="form-group">
                        <label for="upi_id">UPI ID</label>
                        <input type="text" id="upi_id" placeholder="name@upi">
                    </div>
                </div>
                
                <div id="net-banking-details" style="display: none;">
                    <div class="form-group">
                        <label for="bank">Select Bank</label>
                        <select id="bank">
                            <option value="">Select Bank</option>
                            <option value="sbi">State Bank of India</option>
                            <option value="hdfc">HDFC Bank</option>
                            <option value="icici">ICICI Bank</option>
                            <option value="axis">Axis Bank</option>
                            <option value="pnb">Punjab National Bank</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn" id="pay-button" disabled>Pay ₹<?php echo number_format($payment_details['payment_amount'], 2); ?></button>
            </form>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Parivahan Sewa. All rights reserved.</p>
        <p>Ministry of Road Transport & Highways, Government of India</p>
    </footer>

    <script>
        function selectPaymentMethod(method) {
            // Reset all payment methods
            document.querySelectorAll('.payment-method').forEach(function(element) {
                element.classList.remove('selected');
            });
            
            // Hide all payment details
            document.getElementById('credit-card-details').style.display = 'none';
            document.getElementById('upi-details').style.display = 'none';
            document.getElementById('net-banking-details').style.display = 'none';
            
            // Set selected payment method
            document.getElementById('payment_method').value = method;
            
            // Highlight selected method
            document.querySelectorAll('.payment-method').forEach(function(element) {
                if (element.textContent.trim().toLowerCase().includes(method.replace('_', ' '))) {
                    element.classList.add('selected');
                }
            });
            
            // Show relevant payment details
            if (method === 'credit_card' || method === 'debit_card') {
                document.getElementById('credit-card-details').style.display = 'block';
            } else if (method === 'upi') {
                document.getElementById('upi-details').style.display = 'block';
            } else if (method === 'net_banking') {
                document.getElementById('net-banking-details').style.display = 'block';
            }
            
            // Enable pay button
            document.getElementById('pay-button').disabled = false;
        }
    </script>
</body>
</html>