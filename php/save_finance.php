<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "expense_tracker";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("<!DOCTYPE html><html><head><title>Error</title></head><body><h2>Database connection failed: " . $conn->connect_error . "</h2></body></html>");
}

// Check login
if (!isset($_SESSION['user_id'])) {
    echo "<!DOCTYPE html>
    <html><head><title>Error</title></head><body>
    <h2>User not logged in</h2></body></html>";
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch and sanitize form inputs
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $created_at = date('Y-m-d H:i:s');

    // Validation: make sure nothing is empty or invalid
    if (
        empty($type) ||
        $amount <= 0 ||
        empty($category) ||
        empty($reason)
    ) {
        echo "<!DOCTYPE html>
        <html><head><title>Invalid Input</title></head><body>
        <h2>Error: Please fill all fields correctly.</h2>
        <p><a href='../pages/personaltracker.html'>Go Back</a></p>
        </body></html>";
        exit;
    }

    // Prepare insert query
    $sql = "INSERT INTO finance (user_id, type, amount, category, reason, created_at) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdsss", $user_id, $type, $amount, $category, $reason, $created_at);

    if ($stmt->execute()) {
        header("Location: ../pages/personaltracker.php");
        exit();
    } else {
        echo "<!DOCTYPE html>
        <html><head><title>Database Error</title></head><body>
        <h2>Error inserting data: " . htmlspecialchars($stmt->error) . "</h2></body></html>";
    }

    $stmt->close();
}

$conn->close();
?>