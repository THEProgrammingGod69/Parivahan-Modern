<?php
session_start();
require_once 'database/db_connect.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get user's applications
$applications = [];

// Get license applications
$license_sql = "SELECT * FROM license_applications WHERE user_id = ? ORDER BY application_date DESC";
$license_stmt = $conn->prepare($license_sql);
$license_stmt->bind_param("i", $user_id);
$license_stmt->execute();
$license_result = $license_stmt->get_result();

while ($row = $license_result->fetch_assoc()) {
    $row['service_type'] = 'License - ' . ucfirst($row['license_type']);
    $applications[] = $row;
}

// Get vehicle registrations
$vehicle_sql = "SELECT * FROM vehicle_registrations WHERE user_id = ? ORDER BY application_date DESC";
$vehicle_stmt = $conn->prepare($vehicle_sql);
$vehicle_stmt->bind_param("i", $user_id);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();

while ($row = $vehicle_result->fetch_assoc()) {
    $row['service_type'] = 'Vehicle Registration';
    $applications[] = $row;
}

// Get notifications
$notification_sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5";
$notification_stmt = $conn->prepare($notification_sql);
$notification_stmt->bind_param("i", $user_id);
$notification_stmt->execute();
$notification_result = $notification_stmt->get_result();
$notifications = [];

while ($row = $notification_result->fetch_assoc()) {
    $notifications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parivahan - Dashboard</title>
    <style>
        :root {
            --primary-color: #004080;
            --secondary-color: #0066cc;
            --accent-color: #ff6b6b;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 2rem;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
        }

        .sidebar {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .profile-info {
            margin-bottom: 2rem;
        }

        .profile-info h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .profile-detail {
            margin-bottom: 0.5rem;
        }

        .profile-detail strong {
            display: inline-block;
            width: 120px;
        }

        .quick-links h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .quick-links ul {
            list-style: none;
        }

        .quick-links li {
            margin-bottom: 0.5rem;
        }

        .quick-links a {
            color: var(--primary-color);
            text-decoration: none;
            display: block;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .quick-links a:hover {
            background-color: #f0f0f0;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .dashboard-card {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .dashboard-card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .service-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .service-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .service-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .service-item h4 {
            margin-bottom: 0.5rem;
        }

        .service-item a {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .service-item a:hover {
            background-color: var(--secondary-color);
        }

        .application-list {
            margin-top: 1rem;
        }

        .application-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .application-item:last-child {
            border-bottom: none;
        }

        .application-details {
            flex: 1;
        }

        .application-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .application-meta {
            font-size: 0.9rem;
            color: #666;
        }

        .application-status {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: var(--warning-color);
            color: #856404;
        }

        .status-approved {
            background-color: var(--success-color);
            color: white;
        }

        .status-rejected {
            background-color: var(--accent-color);
            color: white;
        }

        .status-in-process {
            background-color: var(--info-color);
            color: white;
        }

        .notification-list {
            margin-top: 1rem;
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .notification-message {
            font-size: 0.9rem;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .no-items {
            padding: 1rem;
            text-align: center;
            color: #666;
        }

        footer {
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 0;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <a href="Parivahan_home.html" class="logo">Parivahan Sewa</a>
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
        <div class="sidebar">
            <div class="profile-info">
                <h3>Profile Information</h3>
                <div class="profile-detail">
                    <strong>Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?>
                </div>
                <div class="profile-detail">
                    <strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?>
                </div>
                <div class="profile-detail">
                    <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                </div>
                <div class="profile-detail">
                    <strong>Phone:</strong> <?php echo htmlspecialchars($user['phone_number']); ?>
                </div>
                <a href="profile.php" style="display: inline-block; margin-top: 1rem; font-size: 0.9rem; color: var(--primary-color);">View Full Profile</a>
            </div>

            <div class="quick-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="license_application.php">Apply for License</a></li>
                    <li><a href="vehicle_registration.php">Register Vehicle</a></li>
                    <li><a href="permit_application.php">Apply for Permit</a></li>
                    <li><a href="tax_payment.php">Pay Road Tax</a></li>
                    <li><a href="documents.php">Upload Documents</a></li>
                </ul>
            </div>
        </div>

        <div class="main-content">
            <div class="dashboard-card">
                <h3>Available Services</h3>
                <div class="service-grid">
                    <div class="service-item">
                        <div class="service-icon">🪪</div>
                        <h4>License Services</h4>
                        <p>Apply for new license or renew existing one</p>
                        <a href="license_application.php">Apply Now</a>
                    </div>

                    <div class="service-item">
                        <div class="service-icon">🚗</div>
                        <h4>Vehicle Registration</h4>
                        <p>Register your vehicle or transfer ownership</p>
                        <a href="vehicle_registration.php">Register Now</a>
                    </div>

                    <div class="service-item">
                        <div class="service-icon">📋</div>
                        <h4>Permits</h4>
                        <p>Apply for various types of transport permits</p>
                        <a href="permit_application.php">Get Permit</a>
                    </div>

                    <div class="service-item">
                        <div class="service-icon">💰</div>
                        <h4>Road Tax</h4>
                        <p>Calculate and pay your road tax online</p>
                        <a href="tax_payment.php">Pay Tax</a>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h3>Recent Applications</h3>
                <div class="application-list">
                    <?php if (count($applications) > 0): ?>
                        <?php foreach (array_slice($applications, 0, 3) as $app): ?>
                            <div class="application-item">
                                <div class="application-details">
                                    <div class="application-title"><?php echo htmlspecialchars($app['service_type']); ?></div>
                                    <div class="application-meta">
                                        Application Date: <?php echo date('d M Y', strtotime($app['application_date'])); ?>
                                        <?php if (isset($app['application_id'])): ?>
                                            | Ref: #<?php echo $app['application_id']; ?>
                                        <?php elseif (isset($app['registration_id'])): ?>
                                            | Ref: #<?php echo $app['registration_id']; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="application-status status-<?php echo strtolower($app['status']); ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div style="text-align: center; margin-top: 1rem;">
                            <a href="applications.php" style="color: var(--primary-color);">View All Applications</a>
                        </div>
                    <?php else: ?>
                        <div class="no-