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
$documents = [];

// Process document upload if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['document'])) {
    $document_type = sanitize_input($_POST['document_type']);
    $document_name = sanitize_input($_POST['document_name']);
    
    // Check if file was uploaded without errors
    if ($_FILES['document']['error'] == 0) {
        $file_name = $_FILES['document']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($file_ext, $allowed_extensions)) {
            // Generate unique filename
            $new_file_name = $user_id . '_' . time() . '_' . $document_type . '.' . $file_ext;
            $upload_path = 'uploads/' . $new_file_name;
            
            // Move uploaded file to destination
            if (move_uploaded_file($_FILES['document']['tmp_name'], $upload_path)) {
                // Insert document record into database
                $sql = "INSERT INTO user_documents (user_id, document_type, document_name, document_path, upload_date) 
                        VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $user_id, $document_type, $document_name, $upload_path);
                
                if ($stmt->execute()) {
                    // Create notification for user
                    $notification_title = "Document Uploaded";
                    $notification_message = "Your document '{$document_name}' has been uploaded successfully.";
                    
                    $notify_sql = "INSERT INTO notifications (user_id, notification_type, title, message) VALUES (?, 'document', ?, ?)";
                    $notify_stmt = $conn->prepare($notify_sql);
                    $notify_stmt->bind_param("iss", $user_id, $notification_title, $notification_message);
                    $notify_stmt->execute();
                    
                    $success = "Document uploaded successfully.";
                } else {
                    $error = "Failed to save document information. Please try again.";
                }
            } else {
                $error = "Failed to upload document. Please try again.";
            }
        } else {
            $error = "Invalid file type. Allowed types: JPG, JPEG, PNG, PDF.";
        }
    } else {
        $error = "Error uploading file. Please try again.";
    }
}

// Delete document if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $document_id = intval($_GET['delete']);
    
    // Get document details to verify ownership and get file path
    $check_sql = "SELECT * FROM user_documents WHERE document_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $document_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 1) {
        $document = $check_result->fetch_assoc();
        $file_path = $document['document_path'];
        
        // Delete from database
        $delete_sql = "DELETE FROM user_documents WHERE document_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $document_id);
        
        if ($delete_stmt->execute()) {
            // Delete file from server
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            $success = "Document deleted successfully.";
        } else {
            $error = "Failed to delete document. Please try again.";
        }
    } else {
        $error = "Invalid document or you don't have permission to delete it.";
    }
    
    // Redirect to remove the query parameter
    header("Location: documents.php");
    exit();
}

// Get user's documents
$documents_sql = "SELECT * FROM user_documents WHERE user_id = ? ORDER BY upload_date DESC";
$documents_stmt = $conn->prepare($documents_sql);
$documents_stmt->bind_param("i", $user_id);
$documents_stmt->execute();
$documents_result = $documents_stmt->get_result();

while ($row = $documents_result->fetch_assoc()) {
    $documents[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parivahan - My Documents</title>
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

        .upload-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .upload-section h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="text"],
        select,
        input[type="file"] {
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
        }

        .btn:hover {
            background-color: var(--secondary-color);
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }

        .btn-danger {
            background-color: var(--accent-color);
        }

        .btn-danger:hover {
            background-color: #e74c3c;
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

        .documents-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .document-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .document-preview {
            height: 180px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #ddd;
        }

        .document-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .document-preview .pdf-icon {
            font-size: 4rem;
            color: #e74c3c;
        }

        .document-info {
            padding: 1rem;
        }

        .document-name {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .document-type {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            background-color: rgba(0, 64, 128, 0.1);
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .document-date {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .document-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
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
        <h2>My Documents</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="upload-section">
            <h3>Upload New Document</h3>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="document_type">Document Type</label>
                    <select id="document_type" name="document_type" required>
                        <option value="">Select Document Type</option>
                        <option value="id_proof">ID Proof</option>
                        <option value="address_proof">Address Proof</option>
                        <option value="photo">Photograph</option>
                        <option value="signature">Signature</option>
                        <option value="medical_certificate">Medical Certificate</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="document_name">Document Name</label>
                    <input type="text" id="document_name" name="document_name" required>
                </div>
                
                <div class="form-group">
                    <label for="document">Select File</label>
                    <input type="file" id="document" name="document" required>
                    <small>Allowed file types: JPG, JPEG, PNG, PDF. Maximum file size: 5MB.</small>
                </div>
                
                <button type="submit" class="btn">Upload Document</button>
            </form>
        </div>
        
        <?php if (empty($documents)): ?>
            <div class="empty-state">
                <p>You haven't uploaded any documents yet.</p>
                <p>Upload your documents to use them in your applications.</p>
            </div>
        <?php else: ?>
            <div class="documents-list">
                <?php foreach ($documents as $document): ?>
                    <div class="document-card">
                        <div class="document-preview">
                            <?php 
                                $file_ext = strtolower(pathinfo($document['document_path'], PATHINFO_EXTENSION));
                                if (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
                                    echo '<img src="' . htmlspecialchars($document['document_path']) . '" alt="Document Preview">';
                                } else {
                                    echo '<div class="pdf-icon">PDF</div>';
                                }
                            ?>
                        </div>
                        
                        <div class="document-info">
                            <div class="document-name">
                                <?php echo htmlspecialchars($document['document_name']); ?>
                            </div>
                            
                            <div class="document-type">
                                <?php 
                                    $type_label = '';
                                    switch ($document['document_type']) {
                                        case 'id_proof':
                                            $type_label = 'ID Proof';
                                            break;
                                        case 'address_proof':
                                            $type_label = 'Address Proof';
                                            break;
                                        case 'photo':
                                            $type_label = 'Photograph';
                                            break;
                                        case 'signature':
                                            $type_label = 'Signature';
                                            break;
                                        case 'medical_certificate':
                                            $type_label = 'Medical Certificate';
                                            break;
                                        default:
                                            $type_label = 'Other';
                                    }
                                    echo $type_label;
                                ?>
                            </div>
                            
                            <div class="document-date">
                                Uploaded on: <?php echo date('d M Y', strtotime($document['upload_date'])); ?>
                            </div>
                            
                            <div class="document-actions">
                                <a href="<?php echo htmlspecialchars($document['document_path']); ?>" target="_blank" class="btn btn-sm">View</a>
                                <a href="?delete=<?php echo $document['document_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this document?')">Delete</a>
                            </div>
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