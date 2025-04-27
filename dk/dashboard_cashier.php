<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Cashier') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Safari - Pet Zoofari</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --savanna-sand: #e9d8a6;
            --safari-green: #588157;
            --jungle-brown: #6c584c;
            --lion-gold: #e6b800;
            --giraffe-orange: #cb904d;
            --elephant-gray: #606c38;
            --zebra-dark: #333333;
            --sky-blue: #a8dadc;
            --grass-light: #e6e8ab;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            background-image: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), 
                              url('https://api.placeholder/1000/600?text=savannahBackground');
            background-attachment: fixed;
            background-size: cover;
            color: var(--jungle-brown);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        .safari-header {
            background-color: var(--safari-green);
            padding: 15px 0;
            position: relative;
            border-bottom: 5px dashed var(--savanna-sand);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .logo-text {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 2.8rem;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            margin: 0;
            position: relative;
        }
        
        .logo-text span {
            color: var(--lion-gold);
        }
        
        .logo-text::before, .logo-text::after {
            content: "🦁";
            font-size: 2rem;
            position: relative;
            top: -5px;
            margin: 0 10px;
        }
        
        .welcome-message {
            text-align: center;
            color: #fff;
            font-size: 1.2rem;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .safari-nav {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            background-color: var(--jungle-brown);
            padding: 5px 15px;
            border-radius: 15px 15px 0 0;
            position: relative;
            z-index: 10;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .safari-nav-item {
            margin: 8px 12px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .safari-nav-link {
            color: var(--savanna-sand);
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px 20px;
            border-radius: 12px;
            background-color: rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .safari-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
            border-color: var(--lion-gold);
        }
        
        .safari-nav-link:active {
            transform: translateY(0);
        }
        
        .nav-icon {
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        
        .animal-icon {
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto 30px;
            padding: 30px 20px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 0 0 20px 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            border: 2px solid var(--safari-green);
            border-top: none;
        }
        
        .dashboard-heading {
            font-family: 'Poppins', sans-serif;
            text-align: center;
            color: var(--safari-green);
            font-size: 2rem;
            margin-bottom: 30px;
            font-weight: 700;
            position: relative;
            padding-bottom: 15px;
        }
        
        .dashboard-heading::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--giraffe-orange);
            border-radius: 4px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .action-card {
            padding: 25px 20px;
            background: linear-gradient(45deg, var(--grass-light), #ffffff);
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid var(--grass-light);
            position: relative;
            overflow: hidden;
        }
        
        .action-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.12);
            border-color: var(--safari-green);
        }
        
        .action-card::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0) 70%);
            opacity: 0;
            transform: rotate(30deg);
            transition: all 0.5s ease;
            z-index: 1;
            pointer-events: none;
        }
        
        .action-card:hover::before {
            opacity: 1;
            transform: rotate(0deg);
        }
        
        .action-icon {
            font-size: 3rem;
            color: var(--safari-green);
            margin-bottom: 15px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }
        
        .action-card:hover .action-icon {
            transform: scale(1.2);
            color: var(--giraffe-orange);
        }
        
        .action-title {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--jungle-brown);
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        
        .action-desc {
            color: var(--zebra-dark);
            font-size: 0.95rem;
            line-height: 1.5;
            position: relative;
            z-index: 2;
        }
        
        .statistics-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: 40px;
            padding: 20px;
            background-color: rgba(var(--safari-green-rgb), 0.1);
            border-radius: 15px;
            border: 1px dashed var(--safari-green);
        }
        
        .stat-box {
            flex: 1;
            min-width: 200px;
            padding: 15px;
            margin: 10px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
            border-bottom: 4px solid var(--jungle-brown);
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            border-bottom-color: var(--lion-gold);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--safari-green);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--jungle-brown);
            font-weight: 600;
        }
        
        .safari-footer {
            text-align: center;
            padding: 20px;
            background-color: var(--jungle-brown);
            color: var(--savanna-sand);
            font-size: 0.9rem;
            border-top: 3px dashed var(--savanna-sand);
            margin-top: 50px;
        }
        
        .animal-trail {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 10px 0;
            overflow: hidden;
            pointer-events: none;
        }
        
        .animal-track {
            display: flex;
            animation: moveRight 30s linear infinite;
        }
        
        @keyframes moveRight {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .paw-print {
            margin: 0 15px;
            opacity: 0.6;
            font-size: 1.5rem;
        }
        
        /* Time and Weather Bar */
        .info-bar {
            background-color: var(--lion-gold);
            color: var(--jungle-brown);
            padding: 8px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .time-display, .weather-display {
            display: flex;
            align-items: center;
        }
        
        .weather-display i {
            margin-right: 5px;
            font-size: 1.2rem;
        }
        
        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--lion-gold);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .safari-nav {
                justify-content: space-around;
            }
            
            .safari-nav-item {
                margin: 5px;
            }
            
            .safari-nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .nav-icon {
                font-size: 1.5rem;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .logo-text {
                font-size: 2.2rem;
            }
            
            .logo-text::before, .logo-text::after {
                font-size: 1.6rem;
            }
        }
        
        /* Animation keyframes */
        @keyframes float {
            0% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        .pulse-animation {
            animation: pulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Info Bar -->
    <div class="info-bar">
        <div class="time-display">
            <i class="fas fa-clock me-2"></i>
            <span id="current-time">Loading time...</span>
        </div>
        <div class="weather-display">
            <i class="fas fa-sun"></i>
            <span>Safari Weather: Sunny, 28°C</span>
        </div>
    </div>

    <!-- Header Section -->
    <header class="safari-header">
        <div class="logo-container">
            <h1 class="logo-text">Pet <span>Zoofari</span></h1>
        </div>
        <p class="welcome-message">Welcome to your safari adventure, Cashier <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>!</p>
    </header>
    
    <!-- Navigation Menu -->
    <nav class="safari-nav">
        <div class="safari-nav-item">
            <a href="process_sales.php" class="safari-nav-link">
                <div class="animal-icon">🦒</div>
                <span>Process Sale</span>
            </a>
        </div>
        <div class="safari-nav-item">
            <a href="add_customer.php" class="safari-nav-link">
                <div class="animal-icon">🦏</div>
                <span>Customers</span>
            </a>
        </div>
        <div class="safari-nav-item">
            <a href="cashier_reports.php" class="safari-nav-link">
                <div class="animal-icon">🦁</div>
                <span>Reports</span>
            </a>
        </div>
        <div class="safari-nav-item">
            <a href="cashier_inventory.php" class="safari-nav-link">
                <div class="animal-icon">🐊</div>
                <span>Inventory</span>
                <div class="notification-badge">3</div>
            </a>
        </div>
        <div class="safari-nav-item">
            <a href="view_products.php" class="safari-nav-link">
                <div class="animal-icon">🦓</div>
                <span>Products</span>
            </a>
        </div>
        <div class="safari-nav-item">
            <a href="cashier_view_orders.php" class="safari-nav-link">
                <div class="animal-icon">🐘</div>
                <span>Orders</span>
            </a>
        </div>
        <div class="safari-nav-item">
            <a href="cashier_view_payments.php" class="safari-nav-link">
                <div class="animal-icon">🦛</div>
                <span>Payments</span>
            </a>
        </div>
        <div class="safari-nav-item">
            <a href="#" id="logout-link" class="safari-nav-link">
                <div class="animal-icon">🚪</div>
                <span>Logout</span>
            </a>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <main class="main-container">
        <h2 class="dashboard-heading">Your Safari Command Center</h2>
        
        <div class="quick-actions">
            <div class="action-card">
                <div class="action-icon float-animation">💲</div>
                <h3 class="action-title">Quick Sale</h3>
                <p class="action-desc">Start a new transaction with a single click</p>
            </div>
            
            <div class="action-card">
                <div class="action-icon float-animation">🔍</div>
                <h3 class="action-title">Search Products</h3>
                <p class="action-desc">Find items by name, category, or barcode</p>
            </div>
            
            <div class="action-card">
                <div class="action-icon float-animation">👤</div>
                <h3 class="action-title">Customer Lookup</h3>
                <p class="action-desc">Find customer details and purchase history</p>
            </div>
            
            <div class="action-card">
                <div class="action-icon float-animation">📊</div>
                <h3 class="action-title">Today's Summary</h3>
                <p class="action-desc">View your sales performance at a glance</p>
            </div>
        </div>
        
        <!-- Statistics Row -->
        <div class="statistics-row">
            <div class="stat-box pulse-animation">
                <div class="stat-number">24</div>
                <div class="stat-label">Today's Sales</div>
            </div>
            
            <div class="stat-box pulse-animation">
                <div class="stat-number">$1,240</div>
                <div class="stat-label">Revenue</div>
            </div>
            
            <div class="stat-box pulse-animation">
                <div class="stat-number">3</div>
                <div class="stat-label">Low Stock Items</div>
            </div>
            
            <div class="stat-box pulse-animation">
                <div class="stat-number">12</div>
                <div class="stat-label">New Customers</div>
            </div>
        </div>
        
        <div class="animal-trail">
            <div class="animal-track">
                <span class="paw-print">🐾</span>
                <span class="paw-print">🐾</span>
                <span class="paw-print">🐾</span>
                <span class="paw-print">🐾</span>
                <span class="paw-print">🐾</span>
                <span class="paw-print">🐾</span>
                <span class="paw-print">🐾</span>
                <span class="paw-print">🐾</span>
                <span class="paw-print">🐾</span>
                <span class="paw-print">🐾</span>
            </div>
        </div>
    </main>
    
    <footer class="safari-footer">
        <p>Pet Zoofari POS System &copy; 2025 | Your Safari Adventure Awaits!</p>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Display current time
        function updateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.getElementById('current-time').textContent = now.toLocaleDateString('en-US', options);
        }
        
        updateTime();
        setInterval(updateTime, 60000);
        
        // Logout confirmation
        document.getElementById('logout-link').addEventListener('click', function(event) {
            event.preventDefault();
            const confirmLogout = confirm('Are you sure you want to leave the Safari?');
            if (confirmLogout) {
                window.location.href = 'logout.php';
            }
        });
        
        // Add hover sound effect to navigation items (commented out to avoid autoplay issues)
        /*
        const navLinks = document.querySelectorAll('.safari-nav-link');
        const hoverSound = new Audio('sounds/jungle-click.mp3');
        
        navLinks.forEach(link => {
            link.addEventListener('mouseenter', () => {
                hoverSound.play();
            });
        });
        */
    </script>
</body>
</html>