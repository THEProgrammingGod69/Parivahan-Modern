<?php
session_start();
require_once 'database/db_connect.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    $mark_sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ? AND user_id = ?";
    $mark_stmt = $conn->prepare($mark_sql);
    $mark_stmt->bind_param("ii", $notification_id, $user_id);
    $mark_stmt->execute();
    
    // Redirect to remove the query parameter
    header("Location: notifications.php");
    exit();
}

// Mark all notifications as read if requested
if (isset($_GET['mark_all_read'])) {
    $mark_all_sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0";
    $mark_all_stmt = $conn->prepare($mark_all_sql);
    $mark_all_stmt->bind_param("i", $user_id);
    $mark_all_stmt->execute();
    
    // Redirect to remove the query parameter
    header("Location: notifications.php");
    exit();
}

// Get user's notifications
$notifications = [];
$notification_sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$notification_stmt = $conn->prepare($notification_sql);
$notification_stmt->bind_param("i", $user_id);
$notification_stmt->execute();
$notification_result = $notification_stmt->get_result();

while ($row = $notification_result->fetch_assoc()) {
    $notifications[] = $row;
}

// Count unread notifications
$unread_count = 0;
foreach ($notifications as $notification) {
    if (!$notification['is_read']) {
        $unread_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parivahan - Notifications</title>
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

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .notification-count {
            background-color: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .mark-all-read {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .mark-all-read:hover {
            text-decoration: underline;
        }

        .notification-list {
            width: 100%;
        }

        .notification-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .notification-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .notification-item.unread {
            border-left: 4px solid var(--primary-color);
            background-color: rgba(0, 64, 128, 0.05);
        }

        .notification-item.unread::before {
            content: '';
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 10px;
            height: 10px;
            background-color: var(--primary-color);
            border-radius: 50%;
        }

        .notification-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .notification-message {
            margin-bottom: 0.5rem;
        }

        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #666;
            margin-top: 1rem;
        }

        .notification-type {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            background-color: rgba(0, 64, 128, 0.1);
            color: var(--primary-color);
        }

        .notification-date {
            color: #666;
        }

        .notification-actions {
            margin-top: 0.5rem;
            text-align: right;
        }

        .notification-actions a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .notification-actions a:hover {
            text-decoration: underline;
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
        <h2>Notifications</h2>
        
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <p>You don't have any notifications yet.</p>
                <p>Notifications about your applications, payments, and other updates will appear here.</p>
            </div>
        <?php else: ?>
            <div class="notification-header">
                <div>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-count"><?php echo $unread_count; ?> unread</span>
                    <?php else: ?>
                        <span>All caught up!</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($unread_count > 0): ?>
                    <a href="?mark_all_read=1" class="mark-all-read">Mark all as read</a>
                <?php endif; ?>
            </div>
            
            <div class="notification-list">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-title">
                            <?php echo htmlspecialchars($notification['title']); ?>
                        </div>
                        
                        <div class="notification-message">
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </div>
                        
                        <div class="notification-meta">
                            <span class="notification-type">
                                <?php 
                                    $type_label = '';
                                    switch ($notification['notification_type']) {
                                        case 'application_update':
                                            $type_label = 'Application';
                                            break;
                                        case 'payment':
                                            $type_label = 'Payment';
                                            break;
                                        case 'document':
                                            $type_label = 'Document';
                                            break;
                                        case 'reminder':
                                            $type_label = 'Reminder';
                                            break;
                                        default:
                                            $type_label = 'Update';
                                    }
                                    echo $type_label;
                                ?>
                            </span>
                            
                            <span class="notification-date">
                                <?php echo date('d M Y, h:i A', strtotime($notification['created_at'])); ?>
                            </span>
                        </div>
                        
                        <?php if (!$notification['is_read']): ?>
                            <div class="notification-actions">
                                <a href="?mark_read=<?php echo $notification['notification_id']; ?>">Mark as read</a>
                            </div>
                        <?php endif; ?>
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