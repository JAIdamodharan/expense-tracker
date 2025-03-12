<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "expense_tracker";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) die("DB connection error");

$user_id = $_SESSION['user_id'] ?? 0;

// Initialize
$totalIncome = $totalExpenses = $savings = 0;
$expenseBreakdown = ["food" => 0, "rent" => 0, "utilities" => 0, "entertainment" => 0];

// Fetch total income
$res = $conn->query("SELECT SUM(amount) AS total FROM finance WHERE user_id=$user_id AND type='income'");
if ($row = $res->fetch_assoc()) $totalIncome = $row['total'] ?? 0;

// Fetch total expenses
$res = $conn->query("SELECT SUM(amount) AS total FROM finance WHERE user_id=$user_id AND type='expense'");
if ($row = $res->fetch_assoc()) $totalExpenses = $row['total'] ?? 0;

$savings = $totalIncome - $totalExpenses;

// Expense category-wise breakdown
$res = $conn->query("SELECT category, SUM(amount) AS total FROM finance WHERE user_id=$user_id AND type='expense' GROUP BY category");
while ($row = $res->fetch_assoc()) {
    $cat = strtolower($row['category']);
    if (isset($expenseBreakdown[$cat])) {
        $expenseBreakdown[$cat] = $row['total'];
    }
}
?>