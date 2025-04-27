<?php
session_start();

// Redirect to login if user is not logged in or is not an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php'; // Ensure this file contains the PDO database connection logic

// Handle Add New Category
if (isset($_POST['add'])) {
    $categoryName = $_POST['categoryName'];
    $description = $_POST['description'];
    $createdByID = $_SESSION['user_id']; // Logged-in user ID

    // Use prepared statements to prevent SQL injection
    $sql = "INSERT INTO Category (CategoryName, Description, CreatedByID) 
            VALUES (:categoryName, :description, :createdByID)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':categoryName', $categoryName);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':createdByID', $createdByID);

    if ($stmt->execute()) {
        // Get the new Category ID for logging
        $newCategoryID = $conn->lastInsertId();

        // Log Action in AuditLog
        $action = "Added Category: $categoryName (ID: $newCategoryID)";
        $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':userID', $createdByID);
        $logStmt->bindParam(':action', $action);
        $logStmt->execute();

        header("Location: categories.php");
        exit();
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
}

// Handle Update Category
if (isset($_POST['update'])) {
    $categoryID = $_POST['categoryID'];
    $categoryName = $_POST['categoryName'];
    $description = $_POST['description'];
    $updatedByID = $_SESSION['user_id']; // Logged-in user ID

    $sql = "UPDATE Category 
            SET CategoryName = :categoryName, Description = :description, UpdatedByID = :updatedByID 
            WHERE CategoryID = :categoryID";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':categoryName', $categoryName);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':updatedByID', $updatedByID);
    $stmt->bindParam(':categoryID', $categoryID);

    if ($stmt->execute()) {
        // Log Action in AuditLog
        $action = "Updated Category: $categoryName (ID: $categoryID)";
        $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':userID', $updatedByID);
        $logStmt->bindParam(':action', $action);
        $logStmt->execute();

        header("Location: categories.php");
        exit();
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $categoryID = intval($_GET['delete']); // Sanitize input
    $deletedByID = $_SESSION['user_id']; // Logged-in user ID

    // Fetch category name for logging before deletion
    $fetchSql = "SELECT CategoryName FROM Category WHERE CategoryID = :categoryID";
    $fetchStmt = $conn->prepare($fetchSql);
    $fetchStmt->bindParam(':categoryID', $categoryID);
    $fetchStmt->execute();
    $category = $fetchStmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        $categoryName = $category['CategoryName'];

        // Perform the deletion
        $sql = "DELETE FROM Category WHERE CategoryID = :categoryID";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':categoryID', $categoryID);

        if ($stmt->execute()) {
            // Log Action in AuditLog
            $action = "Deleted Category: $categoryName (ID: $categoryID)";
            $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bindParam(':userID', $deletedByID);
            $logStmt->bindParam(':action', $action);
            $logStmt->execute();

            header("Location: categories.php");
            exit();
        } else {
            echo "Error: " . $stmt->errorInfo()[2];
        }
    } else {
        echo "Category not found.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Pet Zoofari IMS</title>
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
        <h2>🦓 Category Management - Pet Zoofari IMS</h2>
        <button class="btn btn-secondary theme-toggle" onclick="toggleDarkMode()">🌙 Toggle Dark Mode</button>

        <!-- Search Form -->
        <form action="categories.php" method="GET" class="form-inline mb-4">
            <input type="text" name="search" placeholder="Search by category name" class="form-control mr-2">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="dashboard_admin.php" class="btn btn-secondary ml-2">⬅️ Back to Admin Dashboard</a>
        </form>

        <!-- Add New Category Form -->
        <form action="categories.php" method="POST" class="mb-4">
            <h4>Add New Category</h4>
            <input type="text" name="categoryName" placeholder="Category Name" class="form-control mb-2" required>
            <textarea name="description" placeholder="Description" class="form-control mb-2"></textarea>
            <button type="submit" name="add" class="btn btn-success">Add Category</button>
        </form>

        <!-- Display Category Table -->
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>🆔 Category ID</th>
                    <th>📂 Category Name</th>
                    <th>📝 Description</th>
                    <th>🛠️ Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $search = isset($_GET['search']) ? $_GET['search'] : '';

                $sql = "SELECT * FROM Category";
                if (!empty($search)) {
                    $sql .= " WHERE CategoryName LIKE :search";
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
                            <td>{$row['CategoryID']}</td>
                            <td>{$row['CategoryName']}</td>
                            <td>{$row['Description']}</td>
                            <td>
                                <a href='categories.php?edit={$row['CategoryID']}' class='btn btn-primary btn-sm'>Edit</a>
                                <a href='categories.php?delete={$row['CategoryID']}' class='btn btn-danger btn-sm' onclick='return confirmDelete()'>Delete</a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No categories found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Edit Form -->
        <?php
        if (isset($_GET['edit'])) {
            $categoryID = $_GET['edit'];
            $sql = "SELECT * FROM Category WHERE CategoryID=:categoryID";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':categoryID', $categoryID);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <form action="categories.php" method="POST" class="mb-4">
                <h4>Edit Category</h4>
                <input type="hidden" name="categoryID" value="<?= $row['CategoryID'] ?>">
                <input type="text" name="categoryName" value="<?= $row['CategoryName'] ?>" class="form-control mb-2" required>
                <textarea name="description" class="form-control mb-2"><?= $row['Description'] ?></textarea>
                <button type="submit" name="update" class="btn btn-primary">Update Category</button>
                <a href="categories.php" class="btn btn-secondary">Cancel</a>
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
            return confirm('Are you sure you want to delete this category?');
        }
    </script>
</body>
</html>