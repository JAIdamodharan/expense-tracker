<!DOCTYPE html>
<html lang="en">
<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "expense_tracker";
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) die("DB connection error");

$user_id = $_SESSION['user_id'] ?? 0;

// Total income
$totalIncome = 0;
$res = $conn->query("SELECT SUM(amount) AS total FROM finance WHERE user_id=$user_id AND type='income'");
if ($row = $res->fetch_assoc()) $totalIncome = $row['total'] ?? 0;

// Total expenses
$totalExpenses = 0;
$res = $conn->query("SELECT SUM(amount) AS total FROM finance WHERE user_id=$user_id AND type='expense'");
if ($row = $res->fetch_assoc()) $totalExpenses = $row['total'] ?? 0;

$savings = $totalIncome - $totalExpenses;

// Category-wise breakdown
$expenseBreakdown = ["food" => 0, "rent" => 0, "utilities" => 0, "entertainment" => 0];
$res = $conn->query("SELECT category, SUM(amount) AS total FROM finance WHERE user_id=$user_id AND type='expense' GROUP BY category");
while ($row = $res->fetch_assoc()) {
    $cat = strtolower($row['category']);
    if (isset($expenseBreakdown[$cat])) {
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
      <h1>Financial Tools</h1>
      <ul class="nav-links">
        <li><a href="home.html">Home</a></li>
        <li><a href="convertor.html">Currency Converter</a></li>
        <li><a href="bill_split.html">Bill Splitter</a></li>
        <li><a href="personaltracker.php">Personal Finance</a></li>
        <li><a href="savings_planner.html">Savings Planner</a></li>
      </ul>
    </div>
  </nav>

  <div class="container">
    <h3 class="fade-in">Enter Your Income</h3>
    <form id="incomeForm" action="save_finance.php" method="POST">
      <label for="income">Total Income (₹):</label>
      <input type="number" id="income" name="income" required min="1" placeholder="Enter your income" />
      <button type="submit" name="submit_income">Set Income</button>
      <button type="button" id="resetIncomeButton">Reset</button>
    </form>
  </div>

  <table id="summaryTable" class="fade-in fade-in-left">
    <tr>
      <th>Total Income</th>
      <th>Total Expenses</th>
      <th>Savings</th>
    </tr>
    <tr>
      <td id="totalIncome">₹<?= number_format($totalIncome) ?></td>
      <td id="totalExpenses">₹<?= number_format($totalExpenses) ?></td>
      <td id="savings">₹<?= number_format($savings) ?></td>
    </tr>
  </table>

  <div class="exp">
    <div class="exp_breakdown">
      <h3 class="fade-in fade-in-right">Expenses Breakdown</h3>
      <table class="fade-in fade-in-right">
        <tr><th>Category</th><th>Amount</th></tr>
        <tr><td>Food</td><td id="foodExpense">₹<?= number_format($expenseBreakdown['food']) ?></td></tr>
        <tr><td>Rent</td><td id="rentExpense">₹<?= number_format($expenseBreakdown['rent']) ?></td></tr>
        <tr><td>Utilities</td><td id="utilitiesExpense">₹<?= number_format($expenseBreakdown['utilities']) ?></td></tr>
        <tr><td>Entertainment</td><td id="entertainmentExpense">₹<?= number_format($expenseBreakdown['entertainment']) ?></td></tr>
      </table>
    </div>

    <div class="addexp">
      <h3 class="fade-in">Add Expense</h3>
      <form id="expenseForm" action="save_finance.php" method="POST">
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
        <?php include 'fetch_finance.php'; ?>
      </tbody>
    </table>
  </div>

  <div class="chart-container">
    <canvas id="expenseChart"></canvas>
  </div>

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
      food: <?= $expenseBreakdown['food'] ?>,
      rent: <?= $expenseBreakdown['rent'] ?>,
      utilities: <?= $expenseBreakdown['utilities'] ?>,
      entertainment: <?= $expenseBreakdown['entertainment'] ?>
    };

    let totalIncome = <?= $totalIncome ?>;
    let totalExpenses = <?= $totalExpenses ?>;
    let savings = <?= $savings ?>;

    function formatCurrency(num) {
      return "₹" + num.toLocaleString("en-IN");
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

    document.getElementById("incomeForm").addEventListener("submit", function (e) {
      // Allow form submission to PHP, no JS override required here.
    });

    document.getElementById("expenseForm").addEventListener("submit", function (e) {
      // Allow PHP to process and reload.
    });

    // Reset button functionality
    document.getElementById("resetIncomeButton").addEventListener("click", function () {
      totalIncome = 0;
      totalExpenses = 0;
      savings = 0;
      expenses = { food: 0, rent: 0, utilities: 0, entertainment: 0 };

      totalIncomeEl.textContent = formatCurrency(totalIncome);
      totalExpensesEl.textContent = formatCurrency(totalExpenses);
      savingsEl.textContent = formatCurrency(savings);

      foodExpenseEl.textContent = formatCurrency(0);
      rentExpenseEl.textContent = formatCurrency(0);
      utilitiesExpenseEl.textContent = formatCurrency(0);
      entertainmentExpenseEl.textContent = formatCurrency(0);

      historyTableBody.innerHTML = "";

      expensesChart.data.datasets[0].data = [0, 0, 0, 0];
      expensesChart.update();
    });
  </script>
</body>
</html>