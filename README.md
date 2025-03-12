# 💸 Personal Finance Tracker

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

---

## 📁 Project Structure

finance-tracker/
│
├── css/
│   ├── dashboard.css
│   ├── login.css
│   └── register.css
│
├── images/
│   └── (icons / illustrations)
│
├── pages/
│   ├── login.html
│   ├── register.html
│   └── home.html
│
├── php/
│   ├── dashboard.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── save_finance.php
│   └── fetch_finance.php
│
├── finance.sql         <– Database file (optional export)
└── README.md

---

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

## ⚙️ Setup Instructions

1. Clone this repository:
```bash
git clone https://github.com/yourusername/personal-finance-tracker.git

	2.	Import finance.sql into your MySQL database using phpMyAdmin or CLI.
	3.	Configure database credentials in PHP files (e.g., dashboard.php, login.php, etc.)
	4.	Start Apache & MySQL via XAMPP/WAMP/LAMP
	5.	Access in browser:

http://localhost/personal-finance-tracker/pages/login.html



⸻

📸 Screenshots (Optional)

Add screenshots of your dashboard, forms, and tables here.

⸻

✍️ Author

Jaishree
Third-year Software Engineering Student | VIT Vellore
Passionate about Web Development, AI/ML, and Building Real-world Projects

⸻

📜 License

This project is open-source and free to use under the MIT License.

⸻

💬 Feedback & Contributions

Feel free to fork, suggest improvements, or raise issues.
Pull requests are welcome. Let’s build better finance tools together! 🚀

---
