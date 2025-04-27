<?php
session_start();

// Redirect to login if user is not logged in or is not an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php'; // Ensure this file contains the PDO database connection logic

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

        header("Location: products.php");
        exit();
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
}

// Handle Update Product
if (isset($_POST['update'])) {
    $productID = $_POST['productID'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $brand = $_POST['brand'];
    $price = $_POST['price'];
    $categoryID = $_POST['categoryID'];
    $quantityInStock = $_POST['quantityInStock'];
    $reorderLevel = $_POST['reorderLevel'];
    $updatedByID = $_SESSION['user_id']; // Logged-in user ID

    // Use prepared statements to prevent SQL injection
    $sql = "UPDATE Product SET Name=:name, Description=:description, Brand=:brand, Price=:price, 
            CategoryID=:categoryID, QuantityInStock=:quantityInStock, ReorderLevel=:reorderLevel, UpdatedByID=:updatedByID 
            WHERE ProductID=:productID";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':brand', $brand);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':categoryID', $categoryID);
    $stmt->bindParam(':quantityInStock', $quantityInStock);
    $stmt->bindParam(':reorderLevel', $reorderLevel);
    $stmt->bindParam(':updatedByID', $updatedByID);
    $stmt->bindParam(':productID', $productID);

    if ($stmt->execute()) {
        // Log Action in AuditLog
        $action = "Updated Product: $name (ID: $productID)";
        $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':userID', $updatedByID);
        $logStmt->bindParam(':action', $action);
        $logStmt->execute();

        header("Location: products.php");
        exit();
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
}

// Handle Delete Product
if (isset($_GET['delete'])) {
    $productID = $_GET['delete'];
    $deletedByID = $_SESSION['user_id']; // Logged-in user ID

    // Fetch product details before deletion for logging
    $fetchSql = "SELECT Name FROM Product WHERE ProductID=:productID";
    $fetchStmt = $conn->prepare($fetchSql);
    $fetchStmt->bindParam(':productID', $productID);
    $fetchStmt->execute();
    $product = $fetchStmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $productName = $product['Name'];

        // Use prepared statements to prevent SQL injection
        $sql = "DELETE FROM Product WHERE ProductID=:productID";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':productID', $productID);

        if ($stmt->execute()) {
            // Log Action in AuditLog
            $action = "Deleted Product: $productName (ID: $productID)";
            $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bindParam(':userID', $deletedByID);
            $logStmt->bindParam(':action', $action);
            $logStmt->execute();

            header("Location: products.php");
            exit();
        } else {
            echo "Error: " . $stmt->errorInfo()[2];
        }
    } else {
        echo "Error: Product not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Pet Zoofari IMS</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h2>🦓 Product Management - Pet Zoofari IMS</h2>
        <button class="btn btn-secondary theme-toggle" onclick="toggleDarkMode()">🌙 Toggle Dark Mode</button>

        <!-- Search Form -->
        <form action="products.php" method="GET" class="form-inline mb-4">
            <input type="text" name="search" placeholder="Search by name, brand, or category" class="form-control mr-2">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="dashboard_admin.php" class="btn btn-secondary ml-2">⬅️ Back to Admin Dashboard</a>
        </form>

        <!-- Add New Product Form -->
        <form action="products.php" method="POST" class="mb-4">
            <h4>Add New Product</h4>
            <input type="text" name="name" placeholder="Product Name" class="form-control mb-2" required>
            <textarea name="description" placeholder="Description" class="form-control mb-2"></textarea>
            <input type="text" name="brand" placeholder="Brand" class="form-control mb-2">
            <input type="number" name="price" placeholder="Price" step="0.01" class="form-control mb-2" required>
            <select name="categoryID" class="form-control mb-2" required>
                <?php
                // Fetch categories for the dropdown
                $sql = "SELECT CategoryID, CategoryName FROM Category";
                $stmt = $conn->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='{$row['CategoryID']}'>{$row['CategoryName']}</option>";
                }
                ?>
            </select>
            <input type="number" name="quantityInStock" placeholder="Quantity in Stock" class="form-control mb-2" required>
            <button type="submit" name="add" class="btn btn-success">Add Product</button>
        </form>

        <!-- Display Product Table -->
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>🆔 Product ID</th>
                    <th>📦 Name</th>
                    <th>🏷️ Brand</th>
                    <th>💰 Price</th>
                    <th>📂 Category</th>
                    <th>📊 Quantity in Stock</th>
                    <th>🛠️ Actions</th>
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
                            <td>
                                <a href='products.php?edit={$row['ProductID']}' class='btn btn-primary btn-sm'>Edit</a>
                                <a href='products.php?delete={$row['ProductID']}' class='btn btn-danger btn-sm' onclick='return confirmDelete()'>Delete</a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No products found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Edit Form -->
        <?php
        if (isset($_GET['edit'])) {
            $productID = $_GET['edit'];
            $sql = "SELECT * FROM Product WHERE ProductID=:productID";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':productID', $productID);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <form action="products.php" method="POST" class="mb-4">
                <h4>Edit Product</h4>
                <input type="hidden" name="productID" value="<?= $row['ProductID'] ?>">
                <input type="text" name="name" value="<?= $row['Name'] ?>" class="form-control mb-2" required>
                <textarea name="description" class="form-control mb-2"><?= $row['Description'] ?></textarea>
                <input type="text" name="brand" value="<?= $row['Brand'] ?>" class="form-control mb-2">
                <input type="number" name="price" value="<?= $row['Price'] ?>" step="0.01" class="form-control mb-2" required>
                <select name="categoryID" class="form-control mb-2" required>
                    <?php
                    // Fetch categories for the dropdown
                    $sql = "SELECT CategoryID, CategoryName FROM Category";
                    $stmt = $conn->query($sql);
                    while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $selected = ($category['CategoryID'] == $row['CategoryID']) ? 'selected' : '';
                        echo "<option value='{$category['CategoryID']}' $selected>{$category['CategoryName']}</option>";
                    }
                    ?>
                </select>
                <input type="number" name="quantityInStock" value="<?= $row['QuantityInStock'] ?>" class="form-control mb-2" required>
                <button type="submit" name="update" class="btn btn-primary">Update Product</button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php } ?>
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
            return confirm('Are you sure you want to delete this product?');
        }
    </script>
</body>
</html>