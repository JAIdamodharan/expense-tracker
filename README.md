# Personal Finance Tracker

A simple web-based personal finance tracker built with **HTML**, **CSS**, **PHP**, and **MySQL**. This tool helps users manage their income and expenses, track financial history, and maintain budget clarity through a clean and user-friendly dashboard interface.

---

## 🚀 Features

- 🔐 User Authentication (Login & Register)
- 📥 Income & Expense Entry
- 📊 Expense History Dashboard
- 📂 Category-wise Breakdown
- 💾 Data Storage in MySQL Database
- 🎨 Clean and Modern UI (Blue-Green Theme)

---

## 🛠️ Tech Stack

- **Frontend:** HTML, CSS
- **Backend:** PHP
- **Database:** MySQL
- **Server:** XAMPP / LAMP / WAMP

## 📊 Database Schema

### `users` Table:
| Column     | Type         |
|------------|--------------|
| id         | INT (PK)     |
| name       | VARCHAR      |
| email      | VARCHAR      |
| password   | VARCHAR      |

### `finance` Table:
| Column     | Type         |
|------------|--------------|
| id         | INT (PK)     |
| user_id    | INT (FK)     |
| type       | ENUM('income','expense') |
| amount     | DECIMAL      |
| category   | VARCHAR      |
| reason     | TEXT         |
| created_at | TIMESTAMP    |

---
Screenshots

Index Page
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/872b868e-b806-4846-9f54-f2ca21e6c410" />

Registration Page
<img width="1439" alt="image" src="https://github.com/user-attachments/assets/1598aae4-e77a-46bc-a2e1-f17d708b2c2f" />

Login Page
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/6f4a8b3a-8590-4997-a62d-3b2a4dd38997" />

Home Page 
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/1a066769-0d04-426d-b7a4-bde15964a090" />

Manage Personal Finance - Set Monthly Income
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/0b86f8b6-ffbc-4fcd-98b2-9ea0353c63e5" />

Manage Personal Finance - Add Expenses
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/f098dcbf-f941-445f-b230-b476280995c9" />

Manage Personal Finance - Transaction History fetched from the database
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/acc3a7bd-25fd-46b5-83f5-031beb1cc77d" />

Manage Personal Finance - Pie Chart for expense breakdown 
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/121ba888-5e22-4299-9b99-98a2055bdf2a" />

Manage Personal Finance - Pie Chart for Income breakdown (Regular or Bonus)
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/cd12369c-fceb-46d8-9dfa-17f93c7d951e" />

Manage Personal Finance - A Financial Journal, which categorizes the income, expenditure and savings monthwise
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/7ed0d4a5-d5f5-4e1e-a17d-7d021ad07e82" />

Savings Planner - with progress bar to denote your progress with respect the goal you have set and gives suggestions based on it
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/26247052-45c9-475e-b4c7-2410805481e6" />

Saving Planner - Partner Budgeting (to check the expenditure of each person and get suggestions based on it) 
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/38d6c05f-dcae-4409-be80-836af2f5d869" />

Bill Splitter - to split bills based on the number of people and percentage of split
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/64e83e75-6520-4e56-99f7-b97e1bf9bd54" />

Cuurency Convertor and Net Worth Calculator 
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/b0eca983-0e7f-4483-b4fa-cf6cfe31b6dc" />

Profile Page for each user 
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/9d0777a2-4ee3-461a-bc79-35994c757ceb" />

Financial Summary for the current month
<img width="1440" alt="image" src="https://github.com/user-attachments/assets/b9682a5b-5588-4099-8f90-7d85807d9458" />



---

## ⚙️ Setup Instructions

1. Clone this repository:
```bash
git clone https://github.com/JAIdamodharan/expense-tracker.git
```
	1.	Import finance.sql into your MySQL database using phpMyAdmin or CLI.
	2.	Configure database credentials in PHP files (e.g., dashboard.php, login.php, etc.)
	3.	Start Apache & MySQL via XAMPP/WAMP/LAMP
	4.	Access in browser:

http://localhost/expense_tracker/pages




✍️ Author

Jaishree
Third-year Software Engineering Student | VIT Vellore
Passionate about Web Development, AI/ML, and Building Real-world Projects
