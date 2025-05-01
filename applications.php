<?php
session_start();
require_once 'database/db_connect.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$applications = [];

// Get license applications
$license_sql = "SELECT * FROM license_applications WHERE user_id = ? ORDER BY application_date DESC";
$license_stmt = $conn->prepare($license_sql);
$license_stmt->bind_param("i", $user_id);
$license_stmt->execute();
$license_result = $license_stmt->get_result();

while ($row = $license_result->fetch_assoc()) {
    $row['application_type'] = 'license';
    $row['service_name'] = 'License - ' . ucfirst($row['license_type']);
    $applications[] = $row;
}

// Get vehicle registrations
$vehicle_sql = "SELECT * FROM vehicle_registrations WHERE user_id = ? ORDER BY application_date DESC";
$vehicle_stmt = $conn->prepare($vehicle_sql);
$vehicle_stmt->bind_param("i", $user_id);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();

while ($row = $vehicle_result->fetch_assoc()) {
    $row['application_type'] = 'registration';
    $row['service_name'] = 'Vehicle Registration - ' . ucfirst(str_replace('_', ' ', $row['vehicle_type']));
    $applications[] = $row;
}

// Sort applications by date (newest first)
usort($applications, function($a, $b) {
    return strtotime($b['application_date']) - strtotime($a['application_date']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parivahan - My Applications</title>
    <style>
        :root {
            --primary-color: #004080;
            --secondary-color: #0066cc;
            --accent-color: #ff6b6b;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
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
            max-width: 1000px;
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

        .applications-list {
            width: 100%;
        }

        .application-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .application-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .application-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .application-date {
            color: #666;
            font-size: 0.9rem;
        }

        .application-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            margin-bottom: 0.5rem;
        }

        .detail-label {
            font-weight: 500;
            color: #555;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: rgba(255, 193, 7, 0.2);
            color: #856404;
        }

        .status-approved {
            background-color: rgba(40, 167, 69, 0.2);
            color: #155724;
        }

        .status-rejected {
            background-color: rgba(220, 53, 69, 0.2);
            color: #721c24;
        }

        .status-in-process {
            background-color: rgba(0, 123, 255, 0.2);
            color: #004085;
        }

        .payment-status {
            margin-top: 1rem;
            padding-top: 0.5rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .payment-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .payment-pending {
            background-color: rgba(255, 193, 7, 0.2);
            color: #856404;
        }

        .payment-completed {
            background-color: rgba(40, 167, 69, 0.2);
            color: #155724;
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: var(--secondary-color);
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state p {
            margin-bottom: 1.5rem;
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
        <h2>My Applications</h2>
        
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <p>You haven't submitted any applications yet.</p>
                <div>
                    <a href="license_application.php" class="btn">Apply for License</a>
                    <a href="vehicle_registration.php" class="btn">Register Vehicle</a>
                </div>
            </div>
        <?php else: ?>
            <div class="applications-list">
                <?php foreach ($applications as $application): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="application-title">
                                <?php echo htmlspecialchars($application['service_name']); ?>
                            </div>
                            <div class="application-date">
                                <?php echo date('d M Y, h:i A', strtotime($application['application_date'])); ?>
                            </div>
                        </div>
                        
                        <div class="application-details">
                            <div class="detail-item">
                                <span class="detail-label">Application ID:</span>
                                <?php 
                                    $id_field = $application['application_type'] === 'license' ? 'application_id' : 'registration_id';
                                    echo "#{$application[$id_field]}";
                                ?>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Status:</span>
                                <?php 
                                    $status_class = '';
                                    switch ($application['status']) {
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            break;
                                        case 'approved':
                                            $status_class = 'status-approved';
                                            break;
                                        case 'rejected':
                                            $status_class = 'status-rejected';
                                            break;
                                        case 'in_process':
                                            $status_class = 'status-in-process';
                                            break;
                                    }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                            </div>
                            
                            <?php if ($application['application_type'] === 'license'): ?>
                                <div class="detail-item">
                                    <span class="detail-label">License Type:</span>
                                    <?php echo ucfirst($application['license_type']); ?>
                                </div>
                            <?php else: ?>
                                <div class="detail-item">
                                    <span class="detail-label">Vehicle Type:</span>
                                    <?php echo ucfirst(str_replace('_', ' ', $application['vehicle_type'])); ?>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Vehicle:</span>
                                    <?php echo htmlspecialchars($application['manufacturer'] . ' ' . $application['model']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="detail-item">
                                <span class="detail-label">Amount:</span>
                                ₹<?php echo number_format($application['payment_amount'], 2); ?>
                            </div>
                        </div>
                        
                        <div class="payment-status">
                            <div>
                                <span class="detail-label">Payment Status:</span>
                                <?php 
                                    $payment_class = $application['payment_status'] === 'completed' ? 'payment-completed' : 'payment-pending';
                                ?>
                                <span class="payment-badge <?php echo $payment_class; ?>">
                                    <?php echo ucfirst($application['payment_status']); ?>
                                </span>
                            </div>
                            
                            <?php if ($application['payment_status'] === 'pending'): ?>
                                <a href="payment.php?type=<?php echo $application['application_type']; ?>&id=<?php echo $application[$id_field]; ?>" class="btn btn-sm">Pay Now</a>
                            <?php else: ?>
                                <span class="detail-label">Paid on: <?php echo date('d M Y', strtotime($application['payment_date'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Parivahan Sewa. All rights reserved.</p>
        <p>Ministry of Road Transport & Highways, Government of India</p>
    </footer>
</body>
</html>