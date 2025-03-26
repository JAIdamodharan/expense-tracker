<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "expense_tracker";
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_id = $_SESSION['user_id'];

// Handle income submission
if (isset($_POST['submit_income'])) {
    $income = $_POST['income'];

    // Validate and insert income only if it's a positive number
    if (!empty($income) && is_numeric($income) && $income > 0) {
        $stmt = $conn->prepare("INSERT INTO finance (user_id, type, amount, category, reason) VALUES (?, 'income', ?, 'income', 'Initial income entry')");
        $stmt->bind_param("id", $user_id, $income);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle expense submission
if (isset($_POST['submit_expense'])) {
    $category = trim($_POST['category']);
    $amount = $_POST['amount'];
    $reason = trim($_POST['reason']);

    // Validate and insert expense only if all fields are filled properly
    if (!empty($category) && !empty($reason) && !empty($amount) && is_numeric($amount) && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO finance (user_id, type, amount, category, reason) VALUES (?, 'expense', ?, ?, ?)");
        $stmt->bind_param("idss", $user_id, $amount, $category, $reason);
        $stmt->execute();
        $stmt->close();
    }
}

// Redirect back to personal tracker page after insertion
header("Location: ../pages/personaltracker.php");
exit;
?>