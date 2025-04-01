<?php
    header('ngrok-skip-browser-warning: true');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker System</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Header Section -->
    <header>
        <nav class="main-nav">
            <div class="logo">
                <i class="fas fa-wallet"></i> ExpenseTracker
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#testimonials">Testimonials</a></li>
                <li><a href="login.html" class="nav-login">Login</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <div class="container hero">
        <div class="left-side">
            <h1>Expense Tracker System</h1>
            <p class="tagline">"Budgeting: Because Money Doesn't Grow on Trees... We Checked!"</p>
            <p class="hero-description">Take control of your finances with our easy-to-use expense tracking system. Monitor spending, set budgets, and achieve your financial goals.</p>
            <div class="cta-buttons">
                <button class="login-btn primary-btn"><a href="register.html">Get Started</a></button>
            </div>
        </div>
        <div class="right-side">
            <img src="../images/background.png" alt="Expense Tracking Image">
        </div>
    </div>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <h2>Why Choose Our Expense Tracker?</h2>
        <div class="features-container">
            <div class="feature-card">
                <i class="fas fa-chart-pie feature-icon"></i>
                <h3>Visual Analytics</h3>
                <p>Interactive charts and graphs to visualize your spending patterns and identify areas for improvement.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-mobile-alt feature-icon"></i>
                <h3>User Friendly</h3>
                <p>Track expenses on the go with our fully responsive and easy to use design.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-lock feature-icon"></i>
                <h3>Secure & Private</h3>
                <p>Encryption ensures your financial data remains safe and private at all times.</p>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <h2>How It Works</h2>
        <div class="steps-container">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Create an Account</h3>
                <p>Sign up in less than 2 minutes and set up your profile.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>Track Expenses</h3>
                <p>Easily record expenses and categorize them.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>Analyze Spending</h3>
                <p>View reports and insights about your spending habits.</p>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <h3>Optimize Budget</h3>
                <p>Make informed decisions to improve your financial health.</p>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials">
        <h2>What Our Users Say</h2>
        <div class="testimonial-container">
            <div class="testimonial-card">
                <div class="user-rating">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <p class="testimonial-text">"This expense tracker changed my financial life! I've saved over Rs.30,000 in just six months by identifying unnecessary expenses."</p>
                <div class="user-info">
                    <img src="../images/amb1.jpg" alt="User Avatar" class="user-avatar">
                    <p class="user-name">Amritha Arora</p>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="user-rating">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <p class="testimonial-text">"The visual reports make it so easy to see where my money is going. I finally have control over my spending habits!"</p>
                <div class="user-info">
                    <img src="../images/amb4.jpg" alt="User Avatar" class="user-avatar">
                    <p class="user-name">Deepak Verma</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <h2>Ready to Take Control of Your Finances?</h2>
        <p>Join thousands of users who have transformed their financial habits with our expense tracker.</p>
        <button class="cta-button"><a href="register.html">Start Here</a></button>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section about">
                <h3>About ExpenseTracker</h3>
                <p>We're on a mission to help everyone achieve financial freedom through better expense management and budgeting.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-section contact">
                <h3>Contact Us</h3>
                <p><i class="fas fa-envelope"></i> support@expensetracker.com</p>
                <p><i class="fas fa-phone"></i> +91 83571 84758</p>
                <p><i class="fas fa-map-marker-alt"></i> VIT Vellore</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 ExpenseTracker. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Simple script for modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('demo');
            const btn = document.querySelector('.secondary-btn');
            const closeBtn = document.querySelector('.close-btn');
            
            btn.onclick = function() {
                modal.style.display = 'flex';
            }
            
            closeBtn.onclick = function() {
                modal.style.display = 'none';
            }
            
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>