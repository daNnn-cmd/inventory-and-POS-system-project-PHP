<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php';

// Fetch monthly sales data
$monthly_sales = $conn->query("
    SELECT 
        DATE_FORMAT(OrderDate, '%Y-%m') AS Month, 
        SUM(od.Subtotal) AS TotalSales
    FROM `Order` o
    JOIN OrderDetails od ON o.OrderID = od.OrderID
    GROUP BY DATE_FORMAT(OrderDate, '%Y-%m')
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch inventory data by product
$inventory_data = $conn->query("
    SELECT Name, QuantityInStock FROM Product
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Pet Zoofari</title>
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Import Google Font */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        /* Light Mode Styles */
        :root {
            --bg-color:rgb(232, 240, 186);
            --text-color: #333;
            --sidebar-bg:rgb(255, 213, 0);
            --sidebar-text: #4CAF50;
            --button-bg:rgb(29, 123, 32);
            --button-hover: #45a049;
            --chart-bg: #ffffff;
            --chart-border: #4CAF50;
            --logout-bg: #ff4d4d;
            --logout-hover: #e60000;
        }

        /* Dark Mode Styles */
        [data-theme="dark"] {
            --bg-color:rgb(86, 37, 0);
            --text-color: #e0ffff;
            --sidebar-bg:rgb(99, 69, 28);
            --sidebar-text:rgb(244, 252, 255);
            --button-bg:rgb(25, 39, 25);
            --button-hover:rgb(31, 0, 189);
            --chart-bg: #1e1e1e;
            --chart-border:rgb(11, 85, 0);
            --logout-bg: #dc3545;
            --logout-hover: #c82333;
        }

        body {
            font-family: 'Comic Sans MS', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            display: flex;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-y: auto;
            transition: background-color 0.3s ease;
        }

        .sidebar h2 {
            color: var(--sidebar-text);
            text-align: center;
            font-size: 1.5em;
            margin-bottom: 20px;
        }

        .nav-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 90px;
        }

        .nav-buttons button {
            display: flex;
            align-items: center;
            gap: 10px; /* Space between icon and text */
            background-color: var(--button-bg);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .nav-buttons button:hover {
            background-color: var(--button-hover);
            transform: scale(1.05);
        }

        .nav-buttons button i {
            font-size: 16px; /* Icon size */
        }

        /* Logout Button */
        .logout-btn {
            background-color: var(--logout-bg);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: var(--logout-hover);
        }

        /* Main Content */
        .main-content {
            margin-left: 270px;
            padding: 30px;
            width: calc(100% - 270px);
        }

        .main-content h1 {
            color: var(--sidebar-text);
            font-size: 2em;
            margin-bottom: 20px;
        }

        /* Chart Containers */
        .chart-container {
            background: var(--chart-bg);
            border: 2px solid var(--chart-border);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding:10px;
            margin: 20px 0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            width: 90%;
            height: 450px;
        }

        .chart-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .chart-container h2 {
            color: var(--sidebar-text);
            padding: 10px;
            font-size: 1em;
            margin-bottom: 1px;
            height: 5px;
        }

        /* Dark Mode Toggle */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--button-bg);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #salesBarChart, #inventoryPieChart {
            width: 90% !important;
            height: 400px !important; /* Adjust as needed */
        }

        .theme-toggle:hover {
            background-color: var(--button-hover);
        }
    </style>
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="sidebar">
        <h2>Pet Zoofari Dashboard</h2>
        <div class="nav-buttons">
            <button onclick="location.href='users.php'"><i class="fas fa-users"></i> Users</button>
            <button onclick="location.href='customers.php'"><i class="fas fa-user-friends"></i> Customers</button>
            <button onclick="location.href='categories.php'"><i class="fas fa-list"></i> Categories</button>
            <button onclick="location.href='products.php'"><i class="fas fa-box-open"></i> Products</button>
            <button onclick="location.href='stockin.php'"><i class="fas fa-warehouse"></i> StockIn</button>
            <button onclick="location.href='order.php'"><i class="fas fa-shopping-cart"></i> Orders</button>
            <button onclick="location.href='payment.php'"><i class="fas fa-credit-card"></i> Payments</button>
            <button onclick="location.href='auditlog.php'"><i class="fas fa-history"></i> View AuditLog</button>
            <button onclick="location.href='reports.php'"><i class="fas fa-chart-bar"></i> View Sales Reports</button>
            <button onclick="location.href='inventory.php'"><i class="fas fa-chart-bar"></i> View Inventory</button>
            <button class="logout-btn" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Welcome, Admin</h1>

        <!-- Bar Chart for Monthly Sales -->
        <div class="chart-container">
            <h2>Monthly Sales</h2>
            <canvas id="salesBarChart"></canvas>
        </div>

        <!-- Pie Chart for Inventory Distribution -->
        <div class="chart-container">
            <h2>Inventory Distribution</h2>
            <canvas id="inventoryPieChart"></canvas>
        </div>
    </div>

    <!-- Dark Mode Toggle Button -->
    <button class="theme-toggle" id="themeToggle">🌙 Dark Mode</button>

    <script>
        // Dark Mode Toggle
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;

        // Check localStorage for theme preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            body.setAttribute('data-theme', savedTheme);
            updateToggleText();
        }

        // Toggle Theme
        themeToggle.addEventListener('click', () => {
            if (body.getAttribute('data-theme') === 'dark') {
                body.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
            } else {
                body.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
            updateToggleText();
        });

        // Update Toggle Button Text
        function updateToggleText() {
            if (body.getAttribute('data-theme') === 'dark') {
                themeToggle.textContent = '☀️ Light Mode';
            } else {
                themeToggle.textContent = '🌙 Dark Mode';
            }
        }

        // Bar Chart for Monthly Sales
        const salesBarChart = new Chart(document.getElementById('salesBarChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($monthly_sales, 'Month')) ?>,
                datasets: [{
                    label: 'Monthly Sales',
                    data: <?= json_encode(array_column($monthly_sales, 'TotalSales')) ?>,
                    backgroundColor: 'rgba(76, 175, 80, 0.2)',
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Pie Chart for Inventory Distribution
        const inventoryPieChart = new Chart(document.getElementById('inventoryPieChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($inventory_data, 'Name')) ?>,
                datasets: [{
                    label: 'Quantity in Stock',
                    data: <?= json_encode(array_column($inventory_data, 'QuantityInStock')) ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.raw + ' units';
                                return label;
                            }
                        }
                    }
                }
            }
        });

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>