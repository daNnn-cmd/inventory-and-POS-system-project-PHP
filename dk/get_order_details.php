<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized access');
}

require 'db_connect.php'; // Ensure this file contains the PDO database connection logic

// Check if orderID is provided
if (!isset($_GET['orderID'])) {
    exit('No order ID provided');
}

$orderID = $_GET['orderID'];

// Fetch order information
$orderSql = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.OrderStatus,
            CONCAT(c.FirstName, ' ', c.LastName) AS CustomerName,
            c.Email, c.Phone, c.Address,
            u.Username AS CreatedBy
            FROM `Order` o
            LEFT JOIN Customer c ON o.CustomerID = c.CustomerID
            LEFT JOIN User u ON o.CreatedByID = u.UserID
            WHERE o.OrderID = :orderID";

$orderStmt = $conn->prepare($orderSql);
$orderStmt->bindParam(':orderID', $orderID);
$orderStmt->execute();
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    exit('Order not found');
}

// Fetch order details
$detailsSql = "SELECT od.OrderDetailID, od.Quantity, od.UnitPrice, od.Subtotal,
              p.Name AS ProductName, p.Brand, p.Description
              FROM OrderDetails od
              LEFT JOIN Product p ON od.ProductID = p.ProductID
              WHERE od.OrderID = :orderID";

$detailsStmt = $conn->prepare($detailsSql);
$detailsStmt->bindParam(':orderID', $orderID);
$detailsStmt->execute();
$orderDetails = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch payment information
$paymentSql = "SELECT PaymentID, Amount, PaymentMethod, PaymentStatus, PaymentDate
              FROM Payment
              WHERE OrderID = :orderID";

$paymentStmt = $conn->prepare($paymentSql);
$paymentStmt->bindParam(':orderID', $orderID);
$paymentStmt->execute();
$payments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <!-- Order Information -->
    <div class="row mb-4">
        <div class="col-12">
            <h4>🛒 Order Information</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th>Order ID:</th>
                        <td><?= $order['OrderID'] ?></td>
                        <th>Order Date:</th>
                        <td><?= $order['OrderDate'] ?></td>
                    </tr>
                    <tr>
                        <th>Customer:</th>
                        <td><?= $order['CustomerName'] ?></td>
                        <th>Status:</th>
                        <td>
                            <?php
                            // Display emoji based on status
                            switch($order['OrderStatus']) {
                                case 'Pending': echo '⏳ '; break;
                                case 'Completed': echo '✅ '; break;
                                case 'Cancelled': echo '❌ '; break;
                            }
                            echo $order['OrderStatus'];
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Contact:</th>
                        <td><?= $order['Email'] ?><br><?= $order['Phone'] ?></td>
                        <th>Address:</th>
                        <td><?= $order['Address'] ?></td>
                    </tr>
                    <tr>
                        <th>Total Amount:</th>
                        <td>₱<?= number_format($order['TotalAmount'], 2) ?></td>
                        <th>Created By:</th>
                        <td><?= $order['CreatedBy'] ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="row mb-4">
        <div class="col-12">
            <h4>📦 Order Items</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = 1;
                        foreach ($orderDetails as $item) {
                            echo "<tr>
                                <td>{$counter}</td>
                                <td>{$item['ProductName']} ({$item['Brand']})</td>
                                <td>" . (substr($item['Description'], 0, 50) . (strlen($item['Description']) > 50 ? '...' : '')) . "</td>
                                <td>{$item['Quantity']}</td>
                                <td>₱" . number_format($item['UnitPrice'], 2) . "</td>
                                <td>₱" . number_format($item['Subtotal'], 2) . "</td>
                            </tr>";
                            $counter++;
                        }
                        
                        if (empty($orderDetails)) {
                            echo "<tr><td colspan='6'>No items in this order</td></tr>";
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" style="text-align: right">Total:</th>
                            <th>₱<?= number_format($order['TotalAmount'], 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Information -->
    <div class="row">
        <div class="col-12">
            <h4>💳 Payment Information</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($payments as $payment) {
                            // Display emoji based on payment method
                            $methodEmoji = '';
                            switch($payment['PaymentMethod']) {
                                case 'Cash': $methodEmoji = '💵 '; break;
                                case 'Card': $methodEmoji = '💳 '; break;
                                case 'Online': $methodEmoji = '🖥️ '; break;
                            }
                            
                            // Display emoji based on payment status
                            $statusEmoji = '';
                            switch($payment['PaymentStatus']) {
                                case 'Pending': $statusEmoji = '⏳ '; break;
                                case 'Completed': $statusEmoji = '✅ '; break;
                                case 'Failed': $statusEmoji = '❌ '; break;
                            }
                            
                            echo "<tr>
                                <td>{$payment['PaymentID']}</td>
                                <td>₱" . number_format($payment['Amount'], 2) . "</td>
                                <td>{$methodEmoji}{$payment['PaymentMethod']}</td>
                                <td>{$statusEmoji}{$payment['PaymentStatus']}</td>
                                <td>{$payment['PaymentDate']}</td>
                            </tr>";
                        }
                        
                        if (empty($payments)) {
                            echo "<tr><td colspan='5'>No payment records found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>