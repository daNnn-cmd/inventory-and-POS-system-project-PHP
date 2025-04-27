<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'db_connect.php'; // Ensure this file contains the PDO database connection logic

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
    <title>View Orders and Details Pet Zoofari IMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Add your existing styles here */
        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #E8F5E9; /* Light green background */
            color: #2E7D32; /* Dark green text */
            padding: 20px;
        }

        h2 {
            color: #1B5E20; /* Darker green for headings */
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
        }

        .container {
            background-color: #FFFFFF; /* White background for the container */
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Form Styles */
        form {
            background-color: #F1F8E9; /* Light green form background */
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        form h4 {
            color: #1B5E20; /* Dark green for form headings */
            margin-bottom: 20px;
        }

        .form-control {
            border: 2px solid #C8E6C9; /* Light green border */
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 15px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            border-color: #4CAF50; /* Green border on focus */
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
        }

        /* Button Styles */
        .btn-success {
            background-color: #4CAF50; /* Green button */
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .btn-success:hover {
            background-color: #45A049; /* Darker green on hover */
            transform: scale(1.05);
        }

        .btn-secondary {
            background-color: #81C784; /* Light green secondary button */
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: #66BB6A; /* Darker green on hover */
            transform: scale(1.05);
        }

        /* Table Styles */
        .table {
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .thead-dark {
            background-color: #4CAF50; /* Green table header */
            color: #FFFFFF; /* White text */
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #F1F8E9; /* Light green striped rows */
        }

        .table-hover tbody tr:hover {
            background-color: #C8E6C9; /* Light green hover effect */
        }

        /* Animal Icons */
        .animal-icon {
            width: 24px;
            height: 24px;
            margin-right: 8px;
        }

        /* Animation for Buttons */
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        .btn-success:hover, .btn-secondary:hover {
            animation: bounce 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🦓 View Order - Pet Zoofari IMS</h2>
        <a href="dashboard_cashier.php" class="btn btn-secondary ml-2">⬅️ Back to Cashier Dashboard</a>

        <!-- Search Form -->
        <form action="" method="GET" class="form-inline mb-4">
            <input type="text" name="search" placeholder="Search by customer name" class="form-control mr-2" style="flex-grow: 1;">
            <button type="submit" class="btn btn-success">Search</button>
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
                    <th>🛠️ Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $search = isset($_GET['search']) ? $_GET['search'] : '';

                $sql = "SELECT o.OrderID, c.FirstName, c.LastName, o.OrderDate, 
                        o.TotalAmount, o.OrderStatus, p.PaymentMethod, u.Username 
                        FROM `Order` o 
                        LEFT JOIN Customer c ON o.CustomerID = c.CustomerID 
                        LEFT JOIN Payment p ON o.OrderID = p.OrderID 
                        LEFT JOIN User u ON o.CreatedByID = u.UserID";
                if (!empty($search)) {
                    $sql .= " WHERE CONCAT(c.FirstName, ' ', c.LastName) LIKE :search OR o.OrderID LIKE :search";
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
                        
                        echo "<tr>
                            <td>{$row['OrderID']}</td>
                            <td>{$row['FirstName']} {$row['LastName']}</td>
                            <td>{$row['OrderDate']}</td>
                            <td>₱" . number_format($row['TotalAmount'], 2) . "</td>
                            <td>{$row['OrderStatus']}</td>
                            <td>{$row['PaymentMethod']}</td>
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