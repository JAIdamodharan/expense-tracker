<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.html");
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "expense_tracker";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email);
$stmt->fetch();
$stmt->close();

// Fetch user's expense history
$expenses = [];
$expense_stmt = $conn->prepare("SELECT type, amount, category, reason, created_at FROM finance WHERE user_id = ? ORDER BY created_at DESC");
$expense_stmt->bind_param("i", $user_id);
$expense_stmt->execute();
$result = $expense_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}
$expense_stmt->close();

// Calculate totals
$total_income = 0;
$total_expense = 0;
foreach ($expenses as $exp) {
    if ($exp['type'] === 'income') {
        $total_income += $exp['amount'];
    } elseif ($exp['type'] === 'expense') {
        $total_expense += $exp['amount'];
    }
}
$savings = $total_income - $total_expense;

// Motivational message
if ($total_income == 0) {
    $message = "Let's set your income first and start tracking your progress!";
} elseif ($total_expense == 0) {
    $message = "Great start! Try recording your expenses to understand your spending pattern.";
} elseif ($savings > ($total_income * 0.4)) {
    $message = "Fantastic! You're saving a good portion of your income. Keep it up!";
} elseif ($savings > 0) {
    $message = "You're doing well! Try reducing unnecessary expenses to save more.";
} elseif ($savings < 0) {
    $message = "Oops! You're spending more than you earn. Time to budget smarter!";
} else {
    $message = "You're breaking even. Consider planning better to increase your savings!";
}

// Monthly income and expense summary
$monthly_data = [];
foreach ($expenses as $exp) {
    $month = date('F Y', strtotime($exp['created_at']));
    if (!isset($monthly_data[$month])) {
        $monthly_data[$month] = ['income' => 0, 'expense' => 0];
    }
    if ($exp['type'] === 'income') {
        $monthly_data[$month]['income'] += $exp['amount'];
    } elseif ($exp['type'] === 'expense') {
        $monthly_data[$month]['expense'] += $exp['amount'];
    }
}

// Monthly category breakdown for expenses
$category_data = [];
foreach ($expenses as $exp) {
    if ($exp['type'] === 'expense') {
        $month = date('F Y', strtotime($exp['created_at']));
        $category = $exp['category'];
        
        if (!isset($category_data[$month])) {
            $category_data[$month] = [];
        }
        
        if (!isset($category_data[$month][$category])) {
            $category_data[$month][$category] = 0;
        }
        
        $category_data[$month][$category] += $exp['amount'];
    }
}

// Extract unique categories across all months
$all_categories = [];
foreach ($category_data as $month_categories) {
    foreach (array_keys($month_categories) as $category) {
        if (!in_array($category, $all_categories)) {
            $all_categories[] = $category;
        }
    }
}
sort($all_categories);

$monthlyLabels = json_encode(array_keys($monthly_data));
$monthlyIncome = json_encode(array_column($monthly_data, 'income'));
$monthlyExpense = json_encode(array_column($monthly_data, 'expense'));

// Format category data for charts
$categoryMonths = json_encode(array_keys($category_data));
$categoryNames = json_encode($all_categories);
$categoryDatasets = [];

foreach ($all_categories as $category) {
    $data = [];
    foreach (array_keys($category_data) as $month) {
        $data[] = isset($category_data[$month][$category]) ? $category_data[$month][$category] : 0;
    }
    $categoryDatasets[] = [
        'label' => $category,
        'data' => $data,
        'backgroundColor' => 'rgba(' . rand(100, 255) . ',' . rand(100, 255) . ',' . rand(100, 255) . ',0.7)',
    ];
}

$categoryDatasets = json_encode($categoryDatasets);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <nav class="navbar">
            <div class="nav-content">
                <h1>Expense Tracker</h1>
                <ul class="nav-links">
                    <li><a href="../pages/home.php">Home</a></li>
                    <li><a href="../pages/convertor.html">Currency Converter</a></li>
                    <li><a href="../pages/bill_split.html">Bill Splitter</a></li>
                    <li><a href="../pages/personaltracker.php">Personal Finance</a></li>
                    <li><a href="../pages/savings_planner.html">Savings Planner</a></li>
                    <li><a href="dashboard.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
      </nav>
    <div class="dashboard-container">
        <h2>Welcome, <?php echo htmlspecialchars($name); ?>!</h2><br><br>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>

        <div class="expense-section">
            <h3>Your Expense History</h3>

            <?php if (count($expenses) > 0): ?>
                <table class="expense-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Reason</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($expense['type']); ?></td>
                                <td>₹<?php echo htmlspecialchars(number_format($expense['amount'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                <td><?php echo htmlspecialchars($expense['reason']); ?></td>
                                <td><?php echo htmlspecialchars(date("d M Y, H:i", strtotime($expense['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-expense">No expense records found.</p>
            <?php endif; ?>

            <div class="summary-box" style="margin-top: 40px; padding: 25px; background: #e3f2fd; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center;">
                <h3 style="color: #3797db; margin-bottom: 20px;">Financial Summary</h3>
                <p style="font-size: 18px; color: #333;"><strong>Total Income:</strong> ₹<?php echo number_format($total_income, 2); ?></p>
                <p style="font-size: 18px; color: #333;"><strong>Total Expenditure:</strong> ₹<?php echo number_format($total_expense, 2); ?></p>
                <p style="font-size: 18px; color: #333;"><strong>Savings:</strong> ₹<?php echo number_format($savings, 2); ?></p>
                <p style="font-size: 18px; color: <?php echo ($savings >= 0) ? '#2e7d32' : '#d32f2f'; ?>; font-weight: bold; margin-top: 20px;"><?php echo $message; ?></p>
            </div>
        </div>

        <div class="chart-container">
            <h3 class="chart-title">Monthly Income vs Expenses</h3>
            <canvas id="monthlyChart" height="300"></canvas>
        </div>
        
        <div class="chart-container">
            <h3 class="chart-title">Monthly Expense Categories</h3>
            <canvas id="categoryChart" height="300"></canvas>
        </div>

        <div class="chart-container">
            <h3 class="chart-title">Monthly Category Breakdown</h3>
            
            <?php if (count($category_data) > 0): ?>
                <div class="month-selector" id="monthSelector">
                    <?php foreach (array_keys($category_data) as $index => $month): ?>
                        <button class="month-button <?php echo ($index === 0) ? 'active' : ''; ?>" data-month="<?php echo $month; ?>"><?php echo $month; ?></button>
                    <?php endforeach; ?>
                </div>
                
                <?php foreach ($category_data as $month => $categories): ?>
                    <table class="category-table <?php echo ($month === array_key_first($category_data)) ? 'active' : ''; ?>" id="table-<?php echo str_replace(' ', '-', $month); ?>">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Amount (₹)</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $month_total = array_sum($categories);
                            foreach ($categories as $category => $amount): 
                                $percentage = ($month_total > 0) ? ($amount / $month_total * 100) : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td>₹<?php echo number_format($amount, 2); ?></td>
                                    <td><?php echo number_format($percentage, 2); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="font-weight: bold; background-color: #f2f9ff;">
                                <td>Total</td>
                                <td>₹<?php echo number_format($month_total, 2); ?></td>
                                <td>100%</td>
                            </tr>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-expense">No expense categories to display.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Monthly Income vs Expense Chart
        const monthlyLabels = <?php echo $monthlyLabels; ?>;
        const monthlyIncome = <?php echo $monthlyIncome; ?>;
        const monthlyExpense = <?php echo $monthlyExpense; ?>;

        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [
                    {
                        label: 'Monthly Income',
                        data: monthlyIncome,
                        backgroundColor: '#4CAF50'
                    },
                    {
                        label: 'Monthly Expense',
                        data: monthlyExpense,
                        backgroundColor: '#F44336'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Income vs Expenses'
                    },
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryMonths = <?php echo $categoryMonths; ?>;
        const categoryDatasets = <?php echo $categoryDatasets; ?>;

        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: categoryMonths,
                datasets: categoryDatasets
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Expense Categories by Month'
                    },
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += '₹' + context.raw.toFixed(2);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: false,
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true
                    }
                }
            }
        });

        // Month selector functionality
        const monthButtons = document.querySelectorAll('.month-button');
        const categoryTables = document.querySelectorAll('.category-table');

        monthButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                monthButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                
                // Hide all tables
                categoryTables.forEach(table => table.classList.remove('active'));
                
                // Show the selected table
                const month = this.getAttribute('data-month');
                const tableId = 'table-' + month.replace(' ', '-');
                document.getElementById(tableId).classList.add('active');
            });
        });
    </script>
</body>
</html>