<!DOCTYPE html>
<html lang="en">
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
if ($conn->connect_error) die("DB connection error");

$user_id = $_SESSION['user_id'];

// Total income
$totalIncome = 0;
$res = $conn->query("SELECT SUM(amount) AS total FROM finance WHERE user_id=$user_id AND type='income'");
if ($row = $res->fetch_assoc()) {
    $totalIncome = $row['total'] ?? 0;
}

// Total expenses
$totalExpenses = 0;
$res = $conn->query("SELECT SUM(amount) AS total FROM finance WHERE user_id=$user_id AND type='expense'");
if ($row = $res->fetch_assoc()) {
    $totalExpenses = $row['total'] ?? 0;
}

$savings = $totalIncome - $totalExpenses;

// Category-wise breakdown
$expenseBreakdown = ["food" => 0, "rent" => 0, "utilities" => 0, "entertainment" => 0];
$res = $conn->query("SELECT category, SUM(amount) AS total FROM finance WHERE user_id=$user_id AND type='expense' GROUP BY category");
while ($row = $res->fetch_assoc()) {
    $cat = strtolower(trim($row['category']));
    if (array_key_exists($cat, $expenseBreakdown)) {
        $expenseBreakdown[$cat] = $row['total'];
    }
}
?>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Personal Finance Overview</title>
  <link rel="stylesheet" href="../css/personaltracker.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
            <div class="nav-content">
                <h1>Expense Tracker</h1>
                <ul class="nav-links">
                    <li><a href="home.php">Home</a></li>
                    <li><a href="convertor.html">Currency Converter</a></li>
                    <li><a href="bill_split.html">Bill Splitter</a></li>
                    <li><a href="personaltracker.php">Personal Finance</a></li>
                    <li><a href="savings_planner.html">Savings Planner</a></li>
                    <li><a href="../php/dashboard.php">Profile</a></li>
                    <li><a href="../php/logout.php">Logout</a></li>
                </ul>
            </div>
      </nav>

  <div class="container">
    <h3 class="fade-in">Enter Your Income</h3>
    <form id="incomeForm" action="../php/save_finance.php" method="POST">
      <label for="income">Total Income (₹):</label>
      <input type="number" id="income" name="income" required min="1" placeholder="Enter your income" />
      <button type="submit" name="submit_income">Set Income</button>
    </form>
  </div>

  <table id="summaryTable" class="fade-in fade-in-left">
    <tr>
      <th>Total Income</th>
      <th>Total Expenses</th>
      <th>Savings</th>
    </tr>
    <tr>
      <td id="totalIncome">₹<?= number_format((float)$totalIncome, 2) ?></td>
      <td id="totalExpenses">₹<?= number_format((float)$totalExpenses, 2) ?></td>
      <td id="savings">₹<?= number_format((float)$savings, 2) ?></td>
    </tr>
  </table>

  <div class="exp">
    <div class="exp_breakdown">
      <h3 class="fade-in fade-in-right">Expenses Breakdown</h3>
      <table class="fade-in fade-in-right">
        <tr><th>Category</th><th>Amount</th></tr>
        <tr><td>Food</td><td id="foodExpense">₹<?= number_format((float)$expenseBreakdown['food'], 2) ?></td></tr>
        <tr><td>Rent</td><td id="rentExpense">₹<?= number_format((float)$expenseBreakdown['rent'], 2) ?></td></tr>
        <tr><td>Utilities</td><td id="utilitiesExpense">₹<?= number_format((float)$expenseBreakdown['utilities'], 2) ?></td></tr>
        <tr><td>Entertainment</td><td id="entertainmentExpense">₹<?= number_format((float)$expenseBreakdown['entertainment'], 2) ?></td></tr>
      </table>
    </div>

    <div class="addexp">
      <h3 class="fade-in">Add Expense</h3>
      <form id="incomeForm" action="../php/save_finance.php" method="POST">
        <label for="category">Select Category:</label>
        <select id="category" name="category" required>
          <option value="food">Food</option>
          <option value="rent">Rent</option>
          <option value="utilities">Utilities</option>
          <option value="entertainment">Entertainment</option>
        </select>
        <label for="amount">Amount (in ₹):</label>
        <input type="number" id="amount" name="amount" required min="1" />
        <label for="reason">Reason:</label>
        <input type="text" id="reason" name="reason" required placeholder="e.g. Bought burger" />
        <button type="submit" name="submit_expense">Add Expense</button>
      </form>
    </div>
  </div>

  <div class="exp_history">
    <h3 class="fade-in">Expense History</h3>
    <table id="historyTable" class="fade-in">
      <thead>
        <tr><th>Rupees</th><th>Spent On</th><th>Reason</th></tr>
      </thead>
      <tbody id="historyTableBody">
        <?php include '../php/fetch_finance.php'; ?>
      </tbody>
    </table>
  </div>

  <div class="chart-container">
    <canvas id="expenseChart"></canvas>
    <br><br>
  </div>
  <br><br>
  <script>
    const totalIncomeEl = document.getElementById("totalIncome");
    const totalExpensesEl = document.getElementById("totalExpenses");
    const savingsEl = document.getElementById("savings");

    const foodExpenseEl = document.getElementById("foodExpense");
    const rentExpenseEl = document.getElementById("rentExpense");
    const utilitiesExpenseEl = document.getElementById("utilitiesExpense");
    const entertainmentExpenseEl = document.getElementById("entertainmentExpense");

    const historyTableBody = document.getElementById("historyTableBody");

    let expenses = {
      food: <?= (float)$expenseBreakdown['food'] ?>,
      rent: <?= (float)$expenseBreakdown['rent'] ?>,
      utilities: <?= (float)$expenseBreakdown['utilities'] ?>,
      entertainment: <?= (float)$expenseBreakdown['entertainment'] ?>
    };

    let totalIncome = <?= (float)$totalIncome ?>;
    let totalExpenses = <?= (float)$totalExpenses ?>;
    let savings = <?= (float)$savings ?>;

    function formatCurrency(num) {
      return "₹" + num.toLocaleString("en-IN", { minimumFractionDigits: 2 });
    }

    const expenseData = {
      labels: ["Food", "Rent", "Utilities", "Entertainment"],
      datasets: [{
        label: "Expenses Breakdown",
        data: [expenses.food, expenses.rent, expenses.utilities, expenses.entertainment],
        backgroundColor: ["#4CAF50", "#F44336", "#FF9800", "#2196F3"],
        borderColor: "#fff",
        borderWidth: 1,
      }],
    };

    const config = {
      type: "pie",
      data: expenseData,
      options: {
        responsive: true,
        plugins: {
          legend: { position: "top" },
          title: { display: true, text: "Expenses Breakdown" },
        },
      },
    };

    const expensesChart = new Chart(document.getElementById("expenseChart"), config);
  </script>
</body>
</html>