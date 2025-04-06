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

// Get current month and year for filtering
$current_month = date('m');
$current_year = date('Y');

// Fetch user's expense history (all records)
$expenses = [];
$expense_stmt = $conn->prepare("SELECT type, amount, category, reason, created_at FROM finance WHERE user_id = ? ORDER BY created_at DESC");
$expense_stmt->bind_param("i", $user_id);
$expense_stmt->execute();
$result = $expense_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}
$expense_stmt->close();

// Fetch current month's expenses for summary
$current_month_expenses = [];
$current_month_stmt = $conn->prepare("SELECT type, amount, category, reason, created_at FROM finance WHERE user_id = ? AND MONTH(created_at) = ? AND YEAR(created_at) = ? ORDER BY created_at DESC");
$current_month_stmt->bind_param("iii", $user_id, $current_month, $current_year);
$current_month_stmt->execute();
$current_result = $current_month_stmt->get_result();
while ($row = $current_result->fetch_assoc()) {
    $current_month_expenses[] = $row;
}
$current_month_stmt->close();

// Calculate totals for current month
$total_income = 0;
$total_expense = 0;
foreach ($current_month_expenses as $exp) {
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

// Monthly income and expense summary (for all months)
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

// Monthly category breakdown for expenses (for all months)
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

// Define needs and wants categories
$needs_categories = [
    'Housing' => 'Rent, mortgage payments, property taxes, and home repairs',
    'Utilities' => 'Electricity, water, gas, internet, and phone services',
    'Groceries' => 'Essential food and household supplies',
    'Transportation' => 'Car payments, fuel, public transit, or ride-sharing services',
    'Healthcare' => 'Insurance premiums, medications, and necessary medical visits',
    'Insurance' => 'Health, auto, home/renters, and life insurance',
    'Education' => 'School tuition, books, and required educational supplies',
    'Debt' => 'Minimum payments on loans and credit cards',
    'Rent' => 'Monthly rent payments'
];

$wants_categories = [
    'Entertainment' => 'Movies, concerts, events, books, and games',
    'Dining' => 'Restaurants, cafes, and takeout food',
    'Shopping' => 'Clothing (beyond basics), electronics, and non-essential items',
    'Travel' => 'Vacations, weekend getaways, and recreational trips',
    'Hobbies' => 'Sports equipment, crafting supplies, and hobby-related expenses',
    'Subscription' => 'Streaming services, magazines, and non-essential subscriptions',
    'Personal' => 'Salon visits, spa treatments, and non-essential personal care',
    'Food' => 'Dining out, takeout, and non-essential food items'
];

// Add this function to calculate budget recommendations
function getBudgetRecommendations($expenses, $total_income, $total_expense, $savings) {
    $recommendations = [];
    $categories = [];
    $category_totals = [];
    $category_percentages = [];
    
    // Calculate spending by category
    foreach ($expenses as $exp) {
        if ($exp['type'] === 'expense') {
            $category = $exp['category'];
            if (!isset($category_totals[$category])) {
                $category_totals[$category] = 0;
                $categories[] = $category;
            }
            $category_totals[$category] += $exp['amount'];
        }
    }
    
    // Calculate percentages of total expenses
    foreach ($category_totals as $category => $amount) {
        $category_percentages[$category] = ($total_expense > 0) ? ($amount / $total_expense) * 100 : 0;
    }
    
    // Standard budget allocation recommendations (50/30/20 rule)
    $ideal_needs = 0.5 * $total_income; // 50% for needs
    $ideal_wants = 0.3 * $total_income; // 30% for wants
    $ideal_savings = 0.2 * $total_income; // 20% for savings
    
    // Categorize spending into needs, wants, and savings
    $needs_categories = ['Housing', 'Utilities', 'Groceries', 'Transportation', 'Healthcare', 'Insurance', 'Education', 'Debt', 'Rent'];
    $wants_categories = ['Entertainment', 'Dining', 'Shopping', 'Travel', 'Hobbies', 'Subscription', 'Personal', 'Food'];
    
    $current_needs = 0;
    $current_wants = 0;
    
    foreach ($category_totals as $category => $amount) {
        $lower_category = strtolower($category);
        
        // Check if category is in needs or wants
        $is_need = false;
        foreach ($needs_categories as $need) {
            if (strpos($lower_category, strtolower($need)) !== false) {
                $is_need = true;
                break;
            }
        }
        
        if ($is_need) {
            $current_needs += $amount;
        } else {
            $current_wants += $amount;
        }
    }
    
    // Generate recommendations
    $recommendations[] = [
        'title' => '50/30/20 Budget Rule',
        'description' => 'A popular budgeting strategy that allocates 50% for needs, 30% for wants, and 20% for savings.',
        'current' => [
            'needs' => [
                'amount' => $current_needs,
                'percentage' => ($total_income > 0) ? ($current_needs / $total_income) * 100 : 0
            ],
            'wants' => [
                'amount' => $current_wants,
                'percentage' => ($total_income > 0) ? ($current_wants / $total_income) * 100 : 0
            ],
            'savings' => [
                'amount' => $savings,
                'percentage' => ($total_income > 0) ? ($savings / $total_income) * 100 : 0
            ]
        ],
        'recommended' => [
            'needs' => [
                'amount' => $ideal_needs,
                'percentage' => 50
            ],
            'wants' => [
                'amount' => $ideal_wants,
                'percentage' => 30
            ],
            'savings' => [
                'amount' => $ideal_savings,
                'percentage' => 20
            ]
        ]
    ];
    
    // Identify categories where spending might be too high
    $high_spending_categories = [];
    $category_benchmarks = [
        'Housing' => 30, // 30% of income is common benchmark
        'Food' => 15,    // 10-15% of income
        'Transportation' => 15, // 10-15% of income
        'Entertainment' => 10, // 5-10% of income
        'Utilities' => 10, // 5-10% of income
        'Shopping' => 5,  // 5% of income
        'Healthcare' => 10, // 5-10% of income
        'Debt' => 15,     // 15% max recommended
        'Rent' => 30     // 30% max recommended
    ];
    
    foreach ($category_totals as $category => $amount) {
        $percentage_of_income = ($total_income > 0) ? ($amount / $total_income) * 100 : 0;
        
        // Check against benchmarks
        foreach ($category_benchmarks as $benchmark_category => $max_percentage) {
            if (strpos(strtolower($category), strtolower($benchmark_category)) !== false) {
                if ($percentage_of_income > $max_percentage) {
                    $high_spending_categories[] = [
                        'category' => $category,
                        'current_percentage' => $percentage_of_income,
                        'recommended_percentage' => $max_percentage,
                        'current_amount' => $amount,
                        'recommended_amount' => ($max_percentage / 100) * $total_income,
                        'potential_savings' => $amount - (($max_percentage / 100) * $total_income)
                    ];
                }
                break;
            }
        }
    }
    
    // Generate specific category recommendations
    if (!empty($high_spending_categories)) {
        $recommendations[] = [
            'title' => 'Category-Specific Recommendations',
            'categories' => $high_spending_categories
        ];
    }
    
    // Add emergency fund recommendation if savings are low
    $savings_percentage = ($total_income > 0) ? ($savings / $total_income) * 100 : 0;
    if ($savings_percentage < 10) {
        $emergency_fund_goal = $total_income * 3; // 3 months of income as emergency fund
        $recommendations[] = [
            'title' => 'Emergency Fund Goal',
            'description' => 'Aim to save 3-6 months of expenses for emergencies.',
            'goal_amount' => $emergency_fund_goal,
            'current_savings' => $savings,
            'percentage_complete' => ($emergency_fund_goal > 0) ? min(100, ($savings / $emergency_fund_goal) * 100) : 0
        ];
    }
    
    return $recommendations;
}

// Calculate budget recommendations based on current month's data
$budget_recommendations = getBudgetRecommendations($current_month_expenses, $total_income, $total_expense, $savings);

// Get current month name for display
$current_month_name = date('F Y');

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
    <style>
        /* Additional CSS for Needs vs Wants section */
        .needs-wants-container {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .needs-wants-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }
        .category-columns {
            display: flex;
            gap: 20px;
        }
        .category-column {
            flex: 1;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        .category-column h4 {
            color: #3797db;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .category-item {
            margin-bottom: 12px;
        }
        .category-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 4px;
        }
        .category-desc {
            color: #666;
            font-size: 14px;
        }
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #3797db;
            padding: 12px;
            margin-top: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
    </style>
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
                <h3 style="color: #3797db; margin-bottom: 20px;">Financial Summary for <?php echo $current_month_name; ?></h3>
                <p style="font-size: 18px; color: #333;"><strong>Total Income:</strong> ₹<?php echo number_format($total_income, 2); ?></p>
                <p style="font-size: 18px; color: #333;"><strong>Total Expenditure:</strong> ₹<?php echo number_format($total_expense, 2); ?></p>
                <p style="font-size: 18px; color: #333;"><strong>Savings:</strong> ₹<?php echo number_format($savings, 2); ?></p>
                <p style="font-size: 18px; color: <?php echo ($savings >= 0) ? '#2e7d32' : '#d32f2f'; ?>; font-weight: bold; margin-top: 20px;"><?php echo $message; ?></p>
            </div>
        </div>

        <!-- New Needs vs Wants Section -->
        <div class="needs-wants-container">
            <h3 class="needs-wants-title">Understanding Needs vs. Wants</h3>
            <p>The 50/30/20 budget rule relies on understanding the difference between "needs" and "wants". Here's how we categorize your expenses:</p>
            
            <div class="category-columns">
                <div class="category-column">
                    <h4>Needs (Essential Expenses - 50%)</h4>
                    <?php foreach ($needs_categories as $category => $description): ?>
                        <div class="category-item">
                            <div class="category-name"><?php echo $category; ?></div>
                            <div class="category-desc"><?php echo $description; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="category-column">
                    <h4>Wants (Discretionary Expenses - 30%)</h4>
                    <?php foreach ($wants_categories as $category => $description): ?>
                        <div class="category-item">
                            <div class="category-name"><?php echo $category; ?></div>
                            <div class="category-desc"><?php echo $description; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="info-box">
                <p><strong>Note:</strong> When categorizing your expenses, our system automatically identifies "needs" and "wants" based on the expense category you select. You can improve your budget by being mindful of how you categorize expenses and focusing on reducing non-essential spending.</p>
                <p>The remaining 20% of your income should go towards savings, debt payments above the minimum, investments, and building your emergency fund.</p>
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

        <div class="chart-container" style="margin-top: 40px;">
            <h3 class="chart-title">Budget Recommendations for <?php echo $current_month_name; ?></h3>
            
            <?php if ($total_income > 0 && $total_expense > 0): ?>
                <!-- 50/30/20 Rule Visualization -->
                <div class="recommendation-box">
                    <h4><?php echo $budget_recommendations[0]['title']; ?></h4>
                    <p><?php echo $budget_recommendations[0]['description']; ?></p>
                    
                    <div class="budget-comparison">
                        <div class="budget-column">
                            <h5>Your Current Budget Allocation</h5>
                            <div class="progress-container">
                                <div class="progress-label">Needs</div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(100, $budget_recommendations[0]['current']['needs']['percentage']); ?>%; background-color: #3797db;">
                                        <?php echo number_format($budget_recommendations[0]['current']['needs']['percentage'], 1); ?>%
                                    </div>
                                </div>
                                <div class="progress-amount">₹<?php echo number_format($budget_recommendations[0]['current']['needs']['amount'], 2); ?></div>
                            </div>
                            
                            <div class="progress-container">
                                <div class="progress-label">Wants</div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(100, $budget_recommendations[0]['current']['wants']['percentage']); ?>%; background-color: #ff9800;">
                                        <?php echo number_format($budget_recommendations[0]['current']['wants']['percentage'], 1); ?>%
                                    </div>
                                </div>
                                <div class="progress-amount">₹<?php echo number_format($budget_recommendations[0]['current']['wants']['amount'], 2); ?></div>
                            </div>
                            
                            <div class="progress-container">
                                <div class="progress-label">Savings</div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(100, $budget_recommendations[0]['current']['savings']['percentage']); ?>%; background-color: #4CAF50;">
                                        <?php echo number_format($budget_recommendations[0]['current']['savings']['percentage'], 1); ?>%
                                    </div>
                                </div>
                                <div class="progress-amount">₹<?php echo number_format($budget_recommendations[0]['current']['savings']['amount'], 2); ?></div>
                            </div>
                        </div>
                        
                        <div class="budget-column">
                            <h5>Recommended Budget Allocation</h5>
                            <div class="progress-container">
                                <div class="progress-label">Needs (50%)</div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 50%; background-color: #3797db;">
                                        50%
                                    </div>
                                </div>
                                <div class="progress-amount">₹<?php echo number_format($budget_recommendations[0]['recommended']['needs']['amount'], 2); ?></div>
                            </div>
                            
                            <div class="progress-container">
                                <div class="progress-label">Wants (30%)</div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 30%; background-color: #ff9800;">
                                        30%
                                    </div>
                                </div>
                                <div class="progress-amount">₹<?php echo number_format($budget_recommendations[0]['recommended']['wants']['amount'], 2); ?></div>
                            </div>
                            
                            <div class="progress-container">
                                <div class="progress-label">Savings (20%)</div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 20%; background-color: #4CAF50;">
                                        20%
                                    </div>
                                </div>
                                <div class="progress-amount">₹<?php echo number_format($budget_recommendations[0]['recommended']['savings']['amount'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Category-Specific Recommendations -->
                <?php if (isset($budget_recommendations[1])): ?>
                <div class="recommendation-box">
                    <h4><?php echo $budget_recommendations[1]['title']; ?></h4>
                    
                    <table class="recommendation-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Current Spending</th>
                                <th>Recommended Spending</th>
                                <th>Potential Savings</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($budget_recommendations[1]['categories'] as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['category']); ?></td>
                                <td>
                                    ₹<?php echo number_format($category['current_amount'], 2); ?>
                                    <div class="small-text"><?php echo number_format($category['current_percentage'], 1); ?>% of income</div>
                                </td>
                                <td>
                                    ₹<?php echo number_format($category['recommended_amount'], 2); ?>
                                    <div class="small-text"><?php echo number_format($category['recommended_percentage'], 1); ?>% of income</div>
                                </td>
                                <td class="savings-cell">₹<?php echo number_format($category['potential_savings'], 2); ?></td>
                                <td class="action-cell">
                                    <?php 
                                    $reduction = number_format(($category['current_percentage'] - $category['recommended_percentage']), 1);
                                    echo "Reduce by {$reduction}%";
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- Emergency Fund Recommendation -->
                <?php if (isset($budget_recommendations[2])): ?>
                <div class="recommendation-box">
                    <h4><?php echo $budget_recommendations[2]['title']; ?></h4>
                    <p><?php echo $budget_recommendations[2]['description']; ?></p>
                    
                    <div class="fund-progress">
                        <div class="fund-details">
                            <div>Current: ₹<?php echo number_format($budget_recommendations[2]['current_savings'], 2); ?></div>
                            <div>Goal: ₹<?php echo number_format($budget_recommendations[2]['goal_amount'], 2); ?></div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $budget_recommendations[2]['percentage_complete']; ?>%; background-color: #4CAF50;">
                                <?php echo number_format($budget_recommendations[2]['percentage_complete'], 1); ?>%
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="no-expense">Add income and expenses to get budget recommendations.</p>
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