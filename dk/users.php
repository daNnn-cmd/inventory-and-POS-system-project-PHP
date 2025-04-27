<?php
session_start();

// Redirect to login if user is not logged in or is not an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php'; // Ensure this file contains the PDO database connection logic

// Handle Add New User
if (isset($_POST['add'])) {
    $username = $_POST['username'];
    $password = $_POST['password']; // Store password as plain text
    $role = $_POST['role'];
    $createdBy = $_SESSION['user_id']; // Get current Admin's UserID

    // Use prepared statements to prevent SQL injection
    $sql = "INSERT INTO User (Username, Password, Role) VALUES (:username, :password, :role)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password); // Store plain text password
    $stmt->bindParam(':role', $role);

    if ($stmt->execute()) {
        // Log Action in AuditLog
        $newUserID = $conn->lastInsertId();
        $action = "Created new user: $username";
        $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':userID', $createdBy);
        $logStmt->bindParam(':action', $action);
        $logStmt->execute();

        header("Location: users.php");
        exit();
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
}

// Handle Update User
if (isset($_POST['update'])) {
    $userID = $_POST['userID'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $updatedBy = $_SESSION['user_id'];

    // Update password only if provided
    if (!empty($_POST['password'])) {
        $password = ($_POST['password']);
        $sql = "UPDATE User SET Username=:username, Password=:password, Role=:role WHERE UserID=:userID";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':password', $password);
    } else {
        $sql = "UPDATE User SET Username=:username, Role=:role WHERE UserID=:userID";
        $stmt = $conn->prepare($sql);
    }

    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':userID', $userID);

    if ($stmt->execute()) {
        // Log Action in AuditLog
        $action = "Updated User: $username (ID: $userID)";
        $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':userID', $updatedBy);
        $logStmt->bindParam(':action', $action);
        $logStmt->execute();

        header("Location: users.php");
        exit();
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $userID = $_GET['delete'];
    $deletedBy = $_SESSION['user_id'];

    // Get the username for logging before deleting
    $getUsernameSql = "SELECT Username FROM User WHERE UserID=:userID";
    $getUsernameStmt = $conn->prepare($getUsernameSql);
    $getUsernameStmt->bindParam(':userID', $userID);
    $getUsernameStmt->execute();
    $userRow = $getUsernameStmt->fetch(PDO::FETCH_ASSOC);
    $deletedUsername = $userRow['Username'];

    // Delete User
    $sql = "DELETE FROM User WHERE UserID=:userID";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':userID', $userID);

    if ($stmt->execute()) {
        // Log Action in AuditLog
        $action = "Deleted User: $deletedUsername (ID: $userID)";
        $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':userID', $deletedBy);
        $logStmt->bindParam(':action', $action);
        $logStmt->execute();

        header("Location: users.php");
        exit();
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Pet Zoofari IMS</title>
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
        <h2>🦓 User Management - Pet Zoofari IMS</h2>
        <button class="btn btn-secondary theme-toggle" onclick="toggleDarkMode()">🌙 Toggle Dark Mode</button>

        <!-- Search Form -->
        <form action="users.php" method="GET" class="form-inline mb-4">
            <input type="text" name="search" placeholder="Search by username or role" class="form-control mr-2">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="dashboard_admin.php" class="btn btn-secondary ml-2">⬅️ Back to Admin Dashboard</a>
        </form>

        <!-- Add New User Form -->
        <form action="users.php" method="POST" class="mb-4">
            <h4>Add New User</h4>
            <input type="text" name="username" placeholder="Username" class="form-control mb-2" required>
            <input type="password" name="password" placeholder="Password" class="form-control mb-2" required>
            <select name="role" class="form-control mb-2" required>
                <option value="Admin">Admin</option>
                <option value="Cashier">Cashier</option>
            </select>
            <button type="submit" name="add" class="btn btn-success">Add User</button>
        </form>

        <!-- Display User Table -->
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>🆔 User ID</th>
                    <th>👤 Username</th>
                    <th>🎭 Role</th>
                    <th>🛠️ Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $search = isset($_GET['search']) ? $_GET['search'] : '';

                $sql = "SELECT * FROM User";
                if (!empty($search)) {
                    $sql .= " WHERE Username LIKE :search OR Role LIKE :search";
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
                            <td>{$row['UserID']}</td>
                            <td>{$row['Username']}</td>
                            <td>{$row['Role']}</td>
                            <td>
                                <a href='users.php?edit={$row['UserID']}' class='btn btn-primary btn-sm'>Edit</a>
                                <a href='users.php?delete={$row['UserID']}' class='btn btn-danger btn-sm' onclick='return confirmDelete()'>Delete</a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No users found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Edit Form -->
        <?php
        if (isset($_GET['edit'])) {
            $userID = $_GET['edit'];
            $sql = "SELECT * FROM User WHERE UserID=:userID";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':userID', $userID);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <form action="users.php" method="POST" class="mb-4">
                <h4>Edit User</h4>
                <input type="hidden" name="userID" value="<?= $row['UserID'] ?>">
                <input type="text" name="username" value="<?= $row['Username'] ?>" class="form-control mb-2" required>
                <input type="password" name="password" placeholder="Leave blank to keep current password" class="form-control mb-2">
                <select name="role" class="form-control mb-2" required>
                    <option value="Admin" <?= $row['Role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="Cashier" <?= $row['Role'] === 'Cashier' ? 'selected' : '' ?>>Cashier</option>
                </select>
                <button type="submit" name="update" class="btn btn-primary">Update User</button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
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
            return confirm('Are you sure you want to delete this user?');
        }
    </script>
</body>
</html>