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
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to right, #4CAF50, #3797db);
            color: white;
            min-height: 100vh;
        }

        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background-color: #4CAF50;
            padding: 15px;
            color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .nav-content h1 {
            font-size: 1.5rem;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 20px;
        }

        .nav-links li {
            display: inline;
        }

        .nav-links a {
            text-decoration: none;
            color: white;
            font-weight: bold;
            padding: 10px 15px;
            transition: background 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }

        .dashboard-container {
            max-width: 1000px;
            margin: 120px auto 40px auto;
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            color: #333;
        }

        .dashboard-container h2 {
            font-size: 2em;
            color: #4CAF50;
            text-align: center;
        }

        .dashboard-container p {
            font-size: 18px;
            color: #444;
            text-align: center;
            margin-bottom: 30px;
        }

        .expense-section h3 {
            font-size: 1.6em;
            margin-bottom: 20px;
            color: #3797db;
            text-align: center;
        }

        .expense-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .expense-table th {
            background-color: #3797db;
            color: white;
            padding: 12px;
            font-size: 16px;
            text-align: center;
        }

        .expense-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            font-size: 15px;
            color: #333;
        }

        .expense-table tr:nth-child(even) {
            background-color: #f2f9ff;
        }

        .expense-table tr:hover {
            background-color: #d0f0c0;
        }

        .no-expense {
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
            color: #777;
        }

        @media(max-width: 768px) {
            .nav-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
                margin-top: 10px;
            }

            .dashboard-container {
                margin: 100px 15px 30px 15px;
                padding: 25px;
            }

            .expense-table th, .expense-table td {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="nav-content">
            <h1>Expense Tracker</h1>
            <ul class="nav-links">
                <li><a href="../pages/home.html">Home</a></li>
                <li><a href="dashboard.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

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
                                <td><?php echo htmlspecialchars($expense['amount']); ?></td>
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
        </div>
    </div>

</body>
</html>