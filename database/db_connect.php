<?php
// Database connection parameters
$servername = "localhost";
$username = "root"; // Change this to your database username
$password = ""; // Change this to your database password
$dbname = "parivahan_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
$conn->set_charset("utf8");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input data
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

// Function to hash passwords
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Function to verify passwords
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Function to generate a random token
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to redirect with message
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit();
}
?>