<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$user = "root";
$password = "";
$dbname = "expense_tracker";
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Handle regular or bonus income submission
if (isset($_POST['submit_income'])) {
    $amount = $_POST['income'];
    $income_category = $_POST['income_category'] ?? 'regular'; // Default to 'regular' if not specified
    $reason = $_POST['reason'] ?? null; // Reason field for bonus incomes
    
    if ($amount <= 0) {
        echo "Amount must be greater than zero";
        exit;
    }
    
    // Insert income record
    $sql = "INSERT INTO finance (user_id, type, amount, category, reason, created_at) 
            VALUES (?, 'income', ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idss", $user_id, $amount, $income_category, $reason);
    
    if ($stmt->execute()) {
        header("Location: ../pages/personaltracker.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle expense submission
if (isset($_POST['submit_expense'])) {
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $reason = $_POST['reason'];
    
    if ($amount <= 0) {
        echo "Amount must be greater than zero";
        exit;
    }
    
    $sql = "INSERT INTO finance (user_id, type, amount, category, reason, created_at) 
            VALUES (?, 'expense', ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idss", $user_id, $amount, $category, $reason);
    
    if ($stmt->execute()) {
        header("Location: ../pages/personaltracker.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>