<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'db_connect.php'; // Ensure this file contains the PDO database connection logic

if (isset($_GET['orderID'])) {
    $orderID = $_GET['orderID'];
    
    // Get order information
    $orderSql = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.OrderStatus, 
                 c.FirstName, c.LastName, c.Email, c.Phone, c.Address,
                 p.PaymentMethod, p.PaymentStatus, p.PaymentDate, 
                 u.Username as CreatedBy
                 FROM `Order` o
                 LEFT JOIN Customer c ON o.CustomerID = c.CustomerID
                 LEFT JOIN Payment p ON o.OrderID = p.OrderID
                 LEFT JOIN User u ON o.CreatedByID = u.UserID
                 WHERE o.OrderID = :orderID";
    
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bindParam(':orderID', $orderID);
    $orderStmt->execute();
    
    $orderInfo = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($orderInfo) {
        // Get order details (products)
        $detailsSql = "SELECT od.OrderDetailID, od.Quantity, od.UnitPrice, od.Subtotal,
                       p.Name as ProductName, p.Brand, p.Description
                       FROM OrderDetails od
                       LEFT JOIN Product p ON od.ProductID = p.ProductID
                       WHERE od.OrderID = :orderID";
        
        $detailsStmt = $conn->prepare($detailsSql);
        $detailsStmt->bindParam(':orderID', $orderID);
        $detailsStmt->execute();
        
        $orderDetails = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Display Order Information
        echo "<div class='order-info'>";
        echo "<h4>Order Information</h4>";
        echo "<div class='row'>";
        echo "<div class='col-md-6'>";
        echo "<p><strong>Order ID:</strong> {$orderInfo['OrderID']}</p>";
        echo "<p><strong>Order Date:</strong> {$orderInfo['OrderDate']}</p>";
        echo "<p><strong>Status:</strong> {$orderInfo['OrderStatus']}</p>";
        echo "<p><strong>Created By:</strong> {$orderInfo['CreatedBy']}</p>";
        echo "</div>";
        echo "<div class='col-md-6'>";
        echo "<p><strong>Customer:</strong> {$orderInfo['FirstName']} {$orderInfo['LastName']}</p>";
        echo "<p><strong>Email:</strong> " . ($orderInfo['Email'] ? $orderInfo['Email'] : 'N/A') . "</p>";
        echo "<p><strong>Phone:</strong> " . ($orderInfo['Phone'] ? $orderInfo['Phone'] : 'N/A') . "</p>";
        echo "<p><strong>Address:</strong> " . ($orderInfo['Address'] ? $orderInfo['Address'] : 'N/A') . "</p>";
        echo "</div>";
        echo "</div>"; // End row
        
        echo "<div class='row mt-3'>";
        echo "<div class='col-md-6'>";
        echo "<p><strong>Payment Method:</strong> " . ($orderInfo['PaymentMethod'] ? $orderInfo['PaymentMethod'] : 'N/A') . "</p>";
        echo "<p><strong>Payment Status:</strong> " . ($orderInfo['PaymentStatus'] ? $orderInfo['PaymentStatus'] : 'N/A') . "</p>";
        echo "</div>";
        echo "<div class='col-md-6'>";
        echo "<p><strong>Payment Date:</strong> " . ($orderInfo['PaymentDate'] ? $orderInfo['PaymentDate'] : 'N/A') . "</p>";
        echo "<p><strong>Total Amount:</strong> ₱" . number_format($orderInfo['TotalAmount'], 2) . "</p>";
        echo "</div>";
        echo "</div>"; // End row
        echo "</div>"; // End order-info
        
        // Display Order Details (Products)
        echo "<div class='order-details mt-4'>";
        echo "<h4>Order Details</h4>";
        echo "<table class='table table-striped'>";
        echo "<thead class='thead-dark'>";
        echo "<tr>";
        echo "<th>Product Name</th>";
        echo "<th>Quantity</th>";
        echo "<th>Unit Price</th>";
        echo "<th>Subtotal</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($orderDetails as $detail) {
            echo "<tr>";
            echo "<td>{$detail['ProductName']} " . ($detail['Brand'] ? "({$detail['Brand']})" : "") . "</td>";
            echo "<td>{$detail['Quantity']}</td>";
            echo "<td>₱" . number_format($detail['UnitPrice'], 2) . "</td>";
            echo "<td>₱" . number_format($detail['Subtotal'], 2) . "</td>";
            echo "</tr>";
        }
        
        echo "<tr class='table-success'>";
        echo "<td colspan='3' class='text-right'><strong>Total:</strong></td>";
        echo "<td><strong>₱" . number_format($orderInfo['TotalAmount'], 2) . "</strong></td>";
        echo "</tr>";
        echo "</tbody>";
        echo "</table>";
        echo "</div>"; // End order-details
        
    } else {
        echo "<div class='alert alert-danger'>Order not found</div>";
    }
} else {
    echo "<div class='alert alert-danger'>Order ID not provided</div>";
}
?>