<?php
session_start();

// Redirect to login if user is not logged in or is not a Cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Cashier') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php'; // Ensure this file contains the PDO database connection logic

// Initialize success and error messages
$success = '';
$error = '';

// Handle Add New Customer
if (isset($_POST['add'])) {
    $firstName = htmlspecialchars($_POST['firstName']);
    $lastName = htmlspecialchars($_POST['lastName']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $houseNumber = htmlspecialchars($_POST['houseNumber']);
    $prefixDirection = htmlspecialchars($_POST['prefixDirection']);
    $prefixType = htmlspecialchars($_POST['prefixType']);
    $streetName = htmlspecialchars($_POST['streetName']);
    $streetType = htmlspecialchars($_POST['streetType']);
    $suffixDirection = htmlspecialchars($_POST['suffixDirection']);
    $city = htmlspecialchars($_POST['city']);
    $state = htmlspecialchars($_POST['state']);
    $zip = htmlspecialchars($_POST['zip']);
    $createdBy = $_SESSION['user_id']; // Get current UserID

    $sql = "INSERT INTO Customer (FirstName, LastName, Email, Phone, HouseNumber, PrefixDirection, PrefixType, StreetName, StreetType, SuffixDirection, City, State, Zip) 
            VALUES (:firstName, :lastName, :email, :phone, :houseNumber, :prefixDirection, :prefixType, :streetName, :streetType, :suffixDirection, :city, :state, :zip)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':firstName', $firstName);
    $stmt->bindParam(':lastName', $lastName);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':houseNumber', $houseNumber);
    $stmt->bindParam(':prefixDirection', $prefixDirection);
    $stmt->bindParam(':prefixType', $prefixType);
    $stmt->bindParam(':streetName', $streetName);
    $stmt->bindParam(':streetType', $streetType);
    $stmt->bindParam(':suffixDirection', $suffixDirection);
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':state', $state);
    $stmt->bindParam(':zip', $zip);

    if ($stmt->execute()) {
        // Get the new Customer ID for logging
        $newCustomerID = $conn->lastInsertId();

        // Log Action in AuditLog
        $action = "Added Customer: $firstName $lastName (ID: $newCustomerID)";
        $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':userID', $createdBy);
        $logStmt->bindParam(':action', $action);
        $logStmt->execute();

        // Set success message
        $success = "Customer added successfully!";
    } else {
        $error = "Error: " . $stmt->errorInfo()[2];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer - Pet Zoofari IMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Add your custom styles here */
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
        <h2>🦓 Add New Customer - Pet Zoofari IMS</h2>

        <!-- Display Success or Error Messages -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add New Customer Form -->
        <form action="add_customer.php" method="POST" class="mb-4">
            <h4>Add New Customer</h4>
            <input type="text" name="firstName" placeholder="First Name" class="form-control mb-2" required>
            <input type="text" name="lastName" placeholder="Last Name" class="form-control mb-2" required>
            <input type="email" name="email" placeholder="Email" class="form-control mb-2" required>
            <input type="text" name="phone" placeholder="Phone" class="form-control mb-2" required>
            <!-- Address Components -->
            <input type="text" name="houseNumber" placeholder="House Number" class="form-control mb-2" required>
            <input type="text" name="prefixDirection" placeholder="Prefix Direction (e.g., N, S)" class="form-control mb-2">
            <input type="text" name="prefixType" placeholder="Prefix Type (e.g., Ave, Blvd)" class="form-control mb-2">
            <input type="text" name="streetName" placeholder="Street Name" class="form-control mb-2" required>
            <input type="text" name="streetType" placeholder="Street Type (e.g., St, Rd)" class="form-control mb-2" required>
            <input type="text" name="suffixDirection" placeholder="Suffix Direction (e.g., NW, SE)" class="form-control mb-2">
            <input type="text" name="city" placeholder="City" class="form-control mb-2" required>
            <input type="text" name="state" placeholder="State" class="form-control mb-2" required>
            <input type="text" name="zip" placeholder="ZIP Code" class="form-control mb-2" required>
            <button type="submit" name="add" class="btn btn-success">Add Customer</button>
            <a href="dashboard_cashier.php" class="btn btn-secondary">⬅️ Back to Dashboard</a>
        </form>

        <!-- Display Customer Table -->
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>🆔 Customer ID</th>
                    <th>👤 First Name</th>
                    <th>👤 Last Name</th>
                    <th>📧 Email</th>
                    <th>📞 Phone</th>
                    <th>🏠 Address</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

                $sql = "SELECT * FROM Customer";
                if (!empty($search)) {
                    $sql .= " WHERE FirstName LIKE :search OR LastName LIKE :search OR Email LIKE :search OR Phone LIKE :search";
                }

                $stmt = $conn->prepare($sql);
                if (!empty($search)) {
                    $searchTerm = '%' . $search . '%';
                    $stmt->bindParam(':search', $searchTerm);
                }
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $address = "{$row['HouseNumber']} {$row['PrefixDirection']} {$row['PrefixType']} {$row['StreetName']} {$row['StreetType']} {$row['SuffixDirection']}, {$row['City']}, {$row['State']} {$row['Zip']}";
                        echo "<tr>
                            <td>{$row['CustomerID']}</td>
                            <td>{$row['FirstName']}</td>
                            <td>{$row['LastName']}</td>
                            <td>{$row['Email']}</td>
                            <td>{$row['Phone']}</td>
                            <td>{$address}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No customers found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function confirmDelete() {
            return confirm('Are you sure you want to delete this customer?');
        }
    </script>
</body>
</html>