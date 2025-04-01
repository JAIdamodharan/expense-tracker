<!DOCTYPE html>
<html lang="en">
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Add cache control headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Set the timezone to ensure correct date display - change 'Asia/Kolkata' to your timezone
date_default_timezone_set('Asia/Kolkata');

$host = "localhost";
$user = "root";
$password = "";
$dbname = "expense_tracker";
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) die("DB connection error");

$user_id = $_SESSION['user_id'];
$current_month = date('Y-m');

// Fetch user's name
$username = "";
$name_query = "SELECT name FROM users WHERE id = ?";
$stmt = $conn->prepare($name_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $username = $row['name'];
}
$stmt->close();

// Get current date formatted nicely - will now use the correct timezone
$current_date = date("l, F j, Y");

// Check if user already has regular income for current month
$hasIncomeThisMonth = false;
$incomeThisMonth = 0;
$incomeDate = '';

$checkIncomeQuery = "SELECT amount, created_at FROM finance 
                    WHERE user_id = ? AND type = 'income' AND category = 'regular'
                    AND DATE_FORMAT(created_at, '%Y-%m') = ?";
$stmt = $conn->prepare($checkIncomeQuery);
$stmt->bind_param("is", $user_id, $current_month);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $hasIncomeThisMonth = true;
    $incomeThisMonth = $row['amount'];
    $incomeDate = date("j F Y", strtotime($row['created_at']));
}
$stmt->close();

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

// Income breakdown (regular vs bonus)
$incomeBreakdown = ["regular" => 0, "bonus" => 0];
$res = $conn->query("SELECT category, SUM(amount) AS total FROM finance WHERE user_id=$user_id AND type='income' GROUP BY category");
while ($row = $res->fetch_assoc()) {
    $cat = strtolower(trim($row['category']));
    if (array_key_exists($cat, $incomeBreakdown)) {
        $incomeBreakdown[$cat] = $row['total'];
    }
}

// Get monthly history for journal display
$journal_entries = [];
$journal_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                 SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                 SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
                 FROM finance 
                 WHERE user_id = ?
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                 ORDER BY month DESC";
$stmt = $conn->prepare($journal_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $journal_entries[] = $row;
}
$stmt->close();
?>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Personal Finance Overview</title>
  <link rel="stylesheet" href="../css/personaltracker.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Add meta tag to prevent caching -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
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

       <!-- Date and greeting display -->
    <div class="date-greeting-container">
        <div class="date-greeting-content">
            <h2>Hello <?php echo htmlspecialchars($username); ?>! <br><br><?php echo $current_date; ?><br></h2>
        </div>
    </div>

  <div class="container">
    <h3 class="fade-in">Manage Your Income</h3>
    <div class="income-container">
        <!-- Regular Monthly Income Section -->
        <div class="income-section">
            <h4>Regular Monthly Income</h4>
            <?php if ($hasIncomeThisMonth): ?>
                <div class="monthly-income-notice">
                    <p>You've already recorded your regular income for this month (<?= date('F Y') ?>).</p>
                    <p>Current income: ₹<?= number_format($incomeThisMonth, 2) ?></p>
                    <p class="income-date">Recorded on: <?= $incomeDate ?></p>
                </div>
            <?php else: ?>
                <form id="incomeForm" action="../php/save_finance.php" method="POST">
                  <label for="income">Total Income for <?= date('F Y') ?> (₹):</label>
                  <input type="number" id="income" name="income" required min="1" placeholder="Enter your income" />
                  <input type="hidden" name="income_category" value="regular">
                  <input type="hidden" name="current_month" value="<?= $current_month ?>">
                  <button type="submit" name="submit_income">Set Regular Income</button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Bonus Income Section -->
        <div class="bonus-section">
            <h4>Add Bonus Income</h4>
            <p>Record any additional income such as bonuses, gifts, side gigs, etc.</p>
            <form id="bonusForm" action="../php/save_finance.php" method="POST">
              <label for="bonus">Bonus Amount (₹):</label>
              <input type="number" id="bonus" name="income" required min="1" placeholder="Enter bonus amount" />
              <label for="bonus_reason">Source/Reason:</label>
              <input type="text" id="bonus_reason" name="reason" required placeholder="e.g. Work bonus, Gift, Side gig" />
              <input type="hidden" name="income_category" value="bonus">
              <input type="hidden" name="current_month" value="<?= $current_month ?>">
              <button type="submit" name="submit_income">Add Bonus Income</button>
            </form>
        </div>
    </div>
    
    <!-- Income Breakdown Display -->
    <div class="income-breakdown">
        <h4>Income Breakdown</h4>
        <div class="income-type">
            <span class="income-label">Regular Income:</span>
            <span>₹<?= number_format((float)$incomeBreakdown['regular'], 2) ?></span>
        </div>
        <div class="income-type">
            <span class="income-label">Bonus Income:</span>
            <span>₹<?= number_format((float)$incomeBreakdown['bonus'], 2) ?></span>
        </div>
        <div class="income-type total-row">
            <span class="income-label">Total Income:</span>
            <span>₹<?= number_format((float)$totalIncome, 2) ?></span>
        </div>
    </div>
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
    <h3 class="fade-in">Transaction History</h3>
    <table id="historyTable" class="fade-in">
      <thead>
        <tr><th>Date</th><th>Type</th><th>Rupees</th><th>Category</th><th>Reason</th></tr>
      </thead>
      <tbody id="historyTableBody">
        <?php
        // Modified to include date
        $history_query = "SELECT type, amount, category, reason, created_at FROM finance 
                         WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($history_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $date = date("j M Y", strtotime($row['created_at']));
            $type = ucfirst($row['type']);
            $amount = number_format((float)$row['amount'], 2);
            $category = $row['category'] ? ucfirst($row['category']) : "-";
            $reason = $row['reason'] ? htmlspecialchars($row['reason']) : "-";
            
            echo "<tr>
                <td>{$date}</td>
                <td>{$type}</td>
                <td>₹{$amount}</td>
                <td>{$category}</td>
                <td>{$reason}</td>
            </tr>";
        }
        $stmt->close();
        ?>
      </tbody>
    </table>
  </div>

  <div class="chart-container">
    <canvas id="expenseChart"></canvas>
    <br><br>
  </div>
  
  <!-- Income Chart Container -->
  <div class="chart-container">
    <canvas id="incomeChart"></canvas>
    <br><br>
  </div>
  
  <!-- New Journal/Diary Section -->
  <div class="journal-container">
    <h3 class="journal-title">My Financial Journal</h3>
    
    <?php if (empty($journal_entries)): ?>
        <p>No entries in your financial journal yet. Start by adding your income and expenses!</p>
    <?php else: ?>
        <?php foreach ($journal_entries as $entry): 
            $month_year = date("F Y", strtotime($entry['month'] . "-01"));
            $month_savings = $entry['income'] - $entry['expense'];
            $savings_class = $month_savings >= 0 ? "positive" : "negative";
        ?>
            <div class="journal-entry">
                <div class="journal-date"><?= $month_year ?></div>
                <div class="journal-details">
                    <div class="journal-income">
                        <div>Income</div>
                        <div>₹<?= number_format((float)$entry['income'], 2) ?></div>
                    </div>
                    <div class="journal-expense">
                        <div>Expenses</div>
                        <div>₹<?= number_format((float)$entry['expense'], 2) ?></div>
                    </div>
                    <div class="journal-savings <?= $savings_class ?>">
                        <div>Savings</div>
                        <div>₹<?= number_format((float)$month_savings, 2) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <br><br>
  
  <script>
    // Add a timestamp parameter to prevent JavaScript caching
    const timestamp = new Date().getTime();
    
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
    
    let income = {
      regular: <?= (float)$incomeBreakdown['regular'] ?>,
      bonus: <?= (float)$incomeBreakdown['bonus'] ?>
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

    const expenseConfig = {
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

    const expensesChart = new Chart(document.getElementById("expenseChart"), expenseConfig);
    
    // Create Income Chart
    const incomeData = {
      labels: ["Regular Income", "Bonus Income"],
      datasets: [{
        label: "Income Breakdown",
        data: [income.regular, income.bonus],
        backgroundColor: ["#4CAF50", "#FFC107"],
        borderColor: "#fff",
        borderWidth: 1,
      }],
    };

    const incomeConfig = {
      type: "doughnut",
      data: incomeData,
      options: {
        responsive: true,
        plugins: {
          legend: { position: "top" },
          title: { display: true, text: "Income Breakdown" },
        },
      },
    };

    const incomeChart = new Chart(document.getElementById("incomeChart"), incomeConfig);
    
    // Force a reload of the page when it's loaded from browser cache
    window.addEventListener('pageshow', function(event) {
      if (event.persisted) {
        window.location.reload();
      }
    });
  </script>
</body>
</html>