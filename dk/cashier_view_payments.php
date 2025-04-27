<?php
session_start();

// Redirect if not Cashier
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
    <title>View Payment Pet Zoofari IMS</title>
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
        <h2>🦓 View Payment- Pet Zoofari IMS</h2>
        <a href="dashboard_cashier.php" class="btn btn-secondary ml-2">⬅️ Back to Dashboard</a>

        

        <!-- Display Payment Table -->
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>🆔 Payment ID</th>
                    <th>📦 Order ID</th>
                    <th>👤 Customer</th>
                    <th>🛒 Product</th>
                    <th>🔢 Quantity</th>
                    <th>💰 Amount</th>
                    <th>💳 Method</th>
                    <th>📊 Status</th>
                    <th>📅 Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $search = isset($_GET['search']) ? $_GET['search'] : '';

                $sql = "SELECT 
                            p.PaymentID, 
                            p.OrderID, 
                            p.Amount, 
                            p.PaymentMethod, 
                            p.PaymentStatus, 
                            p.PaymentDate, 
                            c.FirstName, 
                            c.LastName, 
                            od.ProductID, 
                            pr.Name AS ProductName, 
                            od.Quantity
                        FROM Payment p
                        LEFT JOIN `Order` o ON p.OrderID = o.OrderID
                        LEFT JOIN Customer c ON o.CustomerID = c.CustomerID
                        LEFT JOIN OrderDetails od ON o.OrderID = od.OrderID
                        LEFT JOIN Product pr ON od.ProductID = pr.ProductID";
                if (!empty($search)) {
                    $sql .= " WHERE p.OrderID LIKE :search OR p.PaymentMethod LIKE :search OR p.PaymentStatus LIKE :search";
                }

                $stmt = $conn->prepare($sql);
                if (!empty($search)) {
                    $searchTerm = '%' . $search . '%';
                    $stmt->bindParam(':search', $searchTerm);
                }
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>
                            <td>{$row['PaymentID']}</td>
                            <td>{$row['OrderID']}</td>
                            <td>{$row['FirstName']} {$row['LastName']}</td>
                            <td>{$row['ProductName']}</td>
                            <td>{$row['Quantity']}</td>
                            <td>{$row['Amount']}</td>
                            <td>{$row['PaymentMethod']}</td>
                            <td>{$row['PaymentStatus']}</td>
                            <td>{$row['PaymentDate']}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No payments found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

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
            return confirm('Are you sure you want to delete this payment?');
        }
    </script>
</body>
</html>