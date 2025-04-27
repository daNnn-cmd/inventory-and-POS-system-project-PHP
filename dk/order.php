<?php
session_start();

// Redirect to login if user is not logged in or is not an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php'; // Ensure this file contains the PDO database connection logic

// Handle Add New Order
if (isset($_POST['add'])) {
    $customerID = $_POST['customerID'];
    $orderStatus = $_POST['orderStatus'];
    $createdByID = $_SESSION['user_id']; // Logged-in user ID

    // Use prepared statements to prevent SQL injection
    $sql = "INSERT INTO `Order` (CustomerID, OrderStatus, TotalAmount, CreatedByID) 
            VALUES (:customerID, :orderStatus, 0.00, :createdByID)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':customerID', $customerID);
    $stmt->bindParam(':orderStatus', $orderStatus);
    $stmt->bindParam(':createdByID', $createdByID);

    if ($stmt->execute()) {
        // Log Action in AuditLog
        $newOrderID = $conn->lastInsertId();
        $action = "Added Order: CustomerID - $customerID, Status - $orderStatus (ID: $newOrderID)";
        $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':userID', $createdByID);
        $logStmt->bindParam(':action', $action);
        $logStmt->execute();

        header("Location: order.php");
        exit();
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
}

// Handle Update Order
if (isset($_POST['update'])) {
    $orderID = $_POST['orderID'];
    $customerID = $_POST['customerID'];
    $orderStatus = $_POST['orderStatus'];
    $updatedByID = $_SESSION['user_id']; // Logged-in user ID

    // Use prepared statements to prevent SQL injection
    $sql = "UPDATE `Order` SET CustomerID=:customerID, OrderStatus=:orderStatus 
            WHERE OrderID=:orderID";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':customerID', $customerID);
    $stmt->bindParam(':orderStatus', $orderStatus);
    $stmt->bindParam(':orderID', $orderID);

    if ($stmt->execute()) {
        // Log Action in AuditLog
        $action = "Updated Order: CustomerID - $customerID, Status - $orderStatus (ID: $orderID)";
        $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':userID', $updatedByID);
        $logStmt->bindParam(':action', $action);
        $logStmt->execute();

        header("Location: order.php");
        exit();
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
}

// Handle Delete Order
if (isset($_GET['delete'])) {
    $orderID = $_GET['delete'];
    $deletedByID = $_SESSION['user_id']; // Logged-in user ID

    // Fetch Order details before deletion for logging
    $fetchSql = "SELECT CustomerID, TotalAmount, OrderStatus FROM `Order` WHERE OrderID=:orderID";
    $fetchStmt = $conn->prepare($fetchSql);
    $fetchStmt->bindParam(':orderID', $orderID);
    $fetchStmt->execute();
    $order = $fetchStmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $customerID = $order['CustomerID'];
        $totalAmount = $order['TotalAmount'];
        $orderStatus = $order['OrderStatus'];

        // Use prepared statements to prevent SQL injection
        $sql = "DELETE FROM `Order` WHERE OrderID=:orderID";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':orderID', $orderID);

        if ($stmt->execute()) {
            // Log Action in AuditLog
            $action = "Deleted Order: CustomerID - $customerID, TotalAmount - $totalAmount, Status - $orderStatus (ID: $orderID)";
            $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bindParam(':userID', $deletedByID);
            $logStmt->bindParam(':action', $action);
            $logStmt->execute();

            header("Location: order.php");
            exit();
        } else {
            echo "Error: " . $stmt->errorInfo()[2];
        }
    } else {
        echo "Error: Order record not found.";
    }
}

// Calculate Total Amount for an Order
function calculateTotalAmount($conn, $orderID) {
    $sql = "SELECT SUM(Subtotal) AS TotalAmount FROM OrderDetails WHERE OrderID = :orderID";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':orderID', $orderID);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['TotalAmount'] ?? 0;
}

// Update Total Amount in Order table
function updateTotalAmount($conn, $orderID) {
    $totalAmount = calculateTotalAmount($conn, $orderID);
    $sql = "UPDATE `Order` SET TotalAmount = :totalAmount WHERE OrderID = :orderID";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':totalAmount', $totalAmount);
    $stmt->bindParam(':orderID', $orderID);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Pet Zoofari IMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Pet Zoofari Theme Styles */
        :root {
            --primary-color: #4CAF50; /* Green */
            --secondary-color: #FFC107; /* Yellow */
            --background-light:rgb(232, 240, 186);
            --text-light: #333;
            --background-dark:rgb(86, 37, 0);
            --text-dark: #ffffff;
            --table-header-dark: #333333;
        }

        body {
            font-family: 'Comic Sans MS', sans-serif;
            background-color: var(--background-light);
            color: var(--text-light);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        body.dark-mode {
            background-color: var(--background-dark);
            color: var(--text-dark);
        }

        .container {
            margin-top: 30px;
        }

        h2 {
            color: var(--primary-color);
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            border: none;
            color: #333;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-secondary:hover {
            background-color: #e0a800; /* Darker yellow */
            transform: scale(1.05);
        }

        .table {
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .thead-dark {
            background-color: var(--primary-color);
            color: #fff;
        }

        body.dark-mode .thead-dark {
            background-color: var(--table-header-dark);
        }

        /* Ensure table text is white in dark mode */
        body.dark-mode .table,
        body.dark-mode .table th,
        body.dark-mode .table td {
            color: var(--text-dark);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgb(46, 180, 55);
        }

        .table-striped tbody tr:nth-of-type(even) {
            background-color: rgb(189, 224, 32);
        }

        body.dark-mode .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgb(40, 103, 31);
        }

        body.dark-mode .table-striped tbody tr:nth-of-type(even) {
            background-color: rgb(103, 103, 31);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(76, 175, 80, 0.2);
        }

        body.dark-mode .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Dark Mode Toggle Button */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .theme-toggle:hover {
            background-color: #45a049; /* Darker green */
            transform: scale(1.05);
        }

        /* Back Button */
        .back-btn {
            margin-right: 10px;
        }

        /* Animal Icons */
        .animal-icon {
            width: 24px;
            height: 24px;
            margin-right: 8px;
        }
        
        /* Modal styles */
        .modal-content {
            background-color: var(--background-light);
            color: var(--text-light);
            border-radius: 15px;
        }
        
        body.dark-mode .modal-content {
            background-color: var(--background-dark);
            color: var(--text-dark);
        }
        
        .modal-header {
            border-bottom: 2px solid var(--primary-color);
        }
        
        .modal-footer {
            border-top: 2px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🛒 Order Management - Pet Zoofari IMS</h2>
        <button class="btn btn-secondary theme-toggle" onclick="toggleDarkMode()">🌙 Toggle Dark Mode</button>

        <!-- Search Form -->
        <form action="order.php" method="GET" class="form-inline mb-4">
            <input type="text" name="search" placeholder="Search by customer name or status" class="form-control mr-2">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="dashboard_admin.php" class="btn btn-secondary ml-2">⬅️ Back to Admin Dashboard</a>
        </form>

        <!-- Display Order Table -->
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>🆔 Order ID</th>
                    <th>👤 Customer</th>
                    <th>📅 Order Date</th>
                    <th>💰 Total Amount</th>
                    <th>📊 Status</th>
                    <th>💳 Payment Method</th>
                    <th>👤 Created By</th>
                    <th>🛠️ Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $search = isset($_GET['search']) ? $_GET['search'] : '';

                $sql = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.OrderStatus, 
                        CONCAT(c.FirstName, ' ', c.LastName) AS CustomerName, 
                        u.Username, p.PaymentMethod 
                        FROM `Order` o 
                        LEFT JOIN Customer c ON o.CustomerID = c.CustomerID 
                        LEFT JOIN User u ON o.CreatedByID = u.UserID 
                        LEFT JOIN Payment p ON o.OrderID = p.OrderID";
                if (!empty($search)) {
                    $sql .= " WHERE CONCAT(c.FirstName, ' ', c.LastName) LIKE :search OR o.OrderStatus LIKE :search";
                }

                $stmt = $conn->prepare($sql);
                if (!empty($search)) {
                    $searchTerm = '%' . $search . '%';
                    $stmt->bindParam(':search', $searchTerm);
                }
                if (!$stmt->execute()) {
                    echo "Error executing query: " . implode(" ", $stmt->errorInfo());
                    exit();
                }

                if ($stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Update total amount before displaying
                        updateTotalAmount($conn, $row['OrderID']);
                        
                        // Set status emoji
                        $statusEmoji = '';
                        switch($row['OrderStatus']) {
                            case 'Pending': $statusEmoji = '⏳'; break;
                            case 'Completed': $statusEmoji = '✅'; break;
                            case 'Cancelled': $statusEmoji = '❌'; break;
                        }
                        
                        // Set payment method emoji
                        $paymentEmoji = '';
                        switch($row['PaymentMethod']) {
                            case 'Cash': $paymentEmoji = '💵'; break;
                            case 'Card': $paymentEmoji = '💳'; break;
                            case 'Online': $paymentEmoji = '🖥️'; break;
                            default: $paymentEmoji = '❓'; break;
                        }
                        
                        echo "<tr>
                            <td>{$row['OrderID']}</td>
                            <td>{$row['CustomerName']}</td>
                            <td>{$row['OrderDate']}</td>
                            <td>₱" . number_format($row['TotalAmount'], 2) . "</td>
                            <td>{$statusEmoji} {$row['OrderStatus']}</td>
                            <td>{$paymentEmoji} " . ($row['PaymentMethod'] ?? 'Not Set') . "</td>
                            <td>{$row['Username']}</td>
                            <td>
                                <button type='button' class='btn btn-info btn-sm' onclick='showDetails({$row['OrderID']})'>Details</button>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No orders found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Edit Form -->
        <?php
        if (isset($_GET['edit'])) {
            $orderID = $_GET['edit'];
            $sql = "SELECT * FROM `Order` WHERE OrderID=:orderID";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':orderID', $orderID);
            if ($stmt->execute()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    ?>
                    <form action="order.php" method="POST" class="mb-4">
                        <h4>Edit Order</h4>
                        <input type="hidden" name="orderID" value="<?= $row['OrderID'] ?>">
                        <select name="customerID" class="form-control mb-2" required>
                            <?php
                            // Fetch Customers for the dropdown
                            $sql = "SELECT CustomerID, FirstName, LastName FROM Customer";
                            $stmt = $conn->query($sql);
                            while ($customer = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $selected = ($customer['CustomerID'] == $row['CustomerID']) ? 'selected' : '';
                                echo "<option value='{$customer['CustomerID']}' $selected>{$customer['FirstName']} {$customer['LastName']}</option>";
                            }
                            ?>
                        </select>
                        <select name="orderStatus" class="form-control mb-2" required>
                            <option value="Pending" <?= $row['OrderStatus'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Completed" <?= $row['OrderStatus'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Cancelled" <?= $row['OrderStatus'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update" class="btn btn-primary">Update Order</button>
                        <a href="order.php" class="btn btn-secondary">Cancel</a>
                    </form>
                    <?php
                } else {
                    echo "<p>Error: Order record not found.</p>";
                }
            } else {
                echo "<p>Error executing query: " . implode(" ", $stmt->errorInfo()) . "</p>";
            }
        }
        ?>
        
        <!-- Order Details Modal -->
        <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailsModalLabel">Order Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="detailsModalBody">
                        <!-- Details will be loaded here via AJAX -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }

        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }

        function confirmDelete() {
            return confirm('Are you sure you want to delete this order?');
        }
        
        function showDetails(orderID) {
            // Load details via AJAX
            $.ajax({
                url: 'get_order_details.php',
                type: 'GET',
                data: { orderID: orderID },
                success: function(response) {
                    $('#detailsModalBody').html(response);
                    $('#detailsModal').modal('show');
                },
                error: function() {
                    alert('Error loading order details.');
                }
            });
        }
    </script>
</body>
</html>