<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php';

// Fetch sales report
$sales_report = $conn->query("
    SELECT 
        o.OrderID, 
        c.FirstName, 
        c.LastName, 
        o.OrderDate, 
        p.Name AS ProductName, 
        od.Quantity, 
        pm.PaymentMethod, 
        SUM(od.Subtotal) AS TotalAmount
    FROM `Order` o
    JOIN Customer c ON o.CustomerID = c.CustomerID
    JOIN OrderDetails od ON o.OrderID = od.OrderID
    JOIN Product p ON od.ProductID = p.ProductID
    JOIN Payment pm ON o.OrderID = pm.OrderID
    GROUP BY o.OrderID, od.ProductID
")->fetchAll(PDO::FETCH_ASSOC);

// Calculate total sales
$total_sales = 0;
foreach ($sales_report as $sale) {
    $total_sales += $sale['TotalAmount'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Petzoofari Sales Report</title>
    <style>
        body {
            font-family: 'Comic Sans MS', cursive, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #e0f7fa;
            position: relative;
            min-height: 100vh;
        }
        .container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 60px;
        }
        .report {
            padding: 10px;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            width: 100%;
        }
        .sales-report {
            background-color: #fff3e0;
        }
        h1 {
            color: #ff5722;
            margin-bottom: 20px;
            text-align: center;
            font-size: 2em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .sales-report table, .sales-report th, .sales-report td {
            border: 1px solid #ff9800;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        .sales-report th {
            background-color: #ffe0b2;
        }
        .sales-report tr:nth-child(even) {
            background-color: #fff8e1;
        }
        .sales-report tr:hover {
            background-color: #ffcc80;
        }
        .total {
            font-weight: bold;
            background-color: #ffccbc;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-secondary {
            background-color: #ff5722;
            color: #fff;
            border: none;
        }
        .btn-secondary:hover {
            background-color: #e64a19;
        }
        .back-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Petzoofari Sales Report</h1>
    </div>
    <div class="container">
        <div class="report sales-report">
            <h1>Sales Report</h1>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Order Date</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Payment Method</th>
                    <th>Total Amount</th>
                </tr>
                <?php foreach ($sales_report as $sale): ?>
                <tr>
                    <td><?= $sale['OrderID'] ?></td>
                    <td><?= $sale['FirstName'] . ' ' . $sale['LastName'] ?></td>
                    <td><?= $sale['OrderDate'] ?></td>
                    <td><?= $sale['ProductName'] ?></td>
                    <td><?= $sale['Quantity'] ?></td>
                    <td><?= $sale['PaymentMethod'] ?></td>
                    <td><?= number_format($sale['TotalAmount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total">
                    <td colspan="6"><strong>Total Sales</strong></td>
                    <td><?= number_format($total_sales, 2) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <a href="dashboard_admin.php" class="btn btn-secondary back-button">Back</a>
</body>
</html>