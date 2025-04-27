<?php
session_start();

// Redirect to login if user is not logged in or is not an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Cashier') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php'; // Ensure this file contains the PDO database connection logic

$successMessage = ''; // Initialize success message variable

// Handle Add New Product
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $brand = $_POST['brand'];
    $price = $_POST['price'];
    $categoryID = $_POST['categoryID'];
    $quantityInStock = $_POST['quantityInStock'];
    $reorderLevel = $_POST['reorderLevel'];
    $createdByID = $_SESSION['user_id']; // Logged-in user ID

    // Use prepared statements to prevent SQL injection
    $sql = "INSERT INTO Product (Name, Description, Brand, Price, CategoryID, QuantityInStock, ReorderLevel, CreatedByID) 
            VALUES (:name, :description, :brand, :price, :categoryID, :quantityInStock, :reorderLevel, :createdByID)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':brand', $brand);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':categoryID', $categoryID);
    $stmt->bindParam(':quantityInStock', $quantityInStock);
    $stmt->bindParam(':reorderLevel', $reorderLevel);
    $stmt->bindParam(':createdByID', $createdByID);

    if ($stmt->execute()) {
        // Get the new Product ID for logging
        $newProductID = $conn->lastInsertId();

        // Log Action in AuditLog
        $action = "Added Product: $name (ID: $newProductID)";
        $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':userID', $createdByID);
        $logStmt->bindParam(':action', $action);
        $logStmt->execute();

        // Set success message
        $successMessage = "Product added successfully!";
    } else {
        echo "<div class='error-message'>Error: " . $stmt->errorInfo()[2] . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Products - Pet Zoofari</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(to bottom, #f4e7d3, #e0c9a6); /* Light earthy tones */
            color: #5a3e36; /* Dark brown for text */
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            color: #8b5a2b; /* Warm brown for heading */
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1200px;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
            color: #5a3e36;
        }

        input[type="text"], input[type="number"], textarea, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 2px solid #8b5a2b;
            border-radius: 10px;
            font-size: 16px;
            background: #fff;
            color: #5a3e36;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus, input[type="number"]:focus, textarea:focus, select:focus {
            border-color: #00ced1;
            box-shadow: 0 0 10px rgba(0, 206, 209, 0.5);
        }

        button, .btn {
            background-color: #00ced1; /* Teal for buttons */
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        button:hover, .btn:hover {
            background-color: #20b2aa; /* Darker teal on hover */
            transform: scale(1.05);
        }

        .btn-secondary {
            background-color: #8b5a2b; /* Warm brown for secondary button */
        }

        .btn-secondary:hover {
            background-color: #6e4221; /* Darker brown on hover */
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #8b5a2b; /* Warm brown for table header */
            color: #fff;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .actions .btn {
            padding: 5px 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>View Products 🐾</h1>
    <div class="container">
        <!-- Display success message if product is added -->
        <?php if (!empty($successMessage)): ?>
            <div class="success-message"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <!-- Search Form -->
        <form action="view_products.php" method="GET" class="form-inline mb-4">
            <input type="text" name="search" placeholder="Search by name, brand, or category" class="form-control mr-2">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="dashboard_cashier.php" class="btn btn-secondary ml-2">⬅️ Back to Cashier Dashboard</a>
        </form>

        <!-- Display Product Table -->
        <table>
            <thead>
                <tr>
                    <th>🆔 Product ID</th>
                    <th>📦 Name</th>
                    <th>🏷️ Brand</th>
                    <th>💰 Price</th>
                    <th>📂 Category</th>
                    <th>📊 Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $search = isset($_GET['search']) ? $_GET['search'] : '';

                $sql = "SELECT p.*, c.CategoryName 
                        FROM Product p 
                        LEFT JOIN Category c ON p.CategoryID = c.CategoryID";
                if (!empty($search)) {
                    $sql .= " WHERE p.Name LIKE :search OR p.Brand LIKE :search OR c.CategoryName LIKE :search";
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
                            <td>{$row['ProductID']}</td>
                            <td>{$row['Name']}</td>
                            <td>{$row['Brand']}</td>
                            <td>{$row['Price']}</td>
                            <td>{$row['CategoryName']}</td>
                            <td>{$row['QuantityInStock']}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No products found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function confirmDelete() {
            return confirm('Are you sure you want to delete this product?');
        }
    </script>
</body>
</html>