<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Cashier') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php';

// Fetch inventory report
$inventory_report = $conn->query("SELECT p.Name, p.QuantityInStock, p.ReorderLevel FROM Product p")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Petzoofari Inventory Report</title>
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
        .inventory-report {
            background-color: rgb(0, 88, 3);
            color: #fff;
        }
        h1 {
            color: #fff;
            margin-bottom: 20px;
            text-align: center;
            font-size: 2em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .inventory-report table, .inventory-report th, .inventory-report td {
            border: 1px solid rgb(0, 97, 5);
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        .inventory-report th {
            background-color: rgb(0, 110, 5);
        }
        .inventory-report tr:nth-child(even) {
            background-color: #66bb6a;
        }
        .inventory-report tr:hover {
            background-color: rgb(0, 48, 2);
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

        .header h1{
            color: #66bb6a;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Petzoofari Inventory Report</h1>
    </div>
    <div class="container">
        <div class="report inventory-report">
            <h1>Inventory Report</h1>
            <table>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity in Stock</th>
                    
                </tr>
                <?php foreach ($inventory_report as $inventory): ?>
                <tr>
                    <td><?= $inventory['Name'] ?></td>
                    <td><?= $inventory['QuantityInStock'] ?></td>
                   
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <a href="dashboard_cashier.php" class="btn btn-secondary back-button">Back</a>
</body>
</html>