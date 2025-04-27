<?php
session_start();

// Redirect to login if user is not logged in or is not an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php'; // Ensure this file contains the PDO database connection logic

// Handle Add New StockIn
if (isset($_POST['add'])) {
    $supplier = htmlspecialchars($_POST['supplier']);
    $invoiceDate = htmlspecialchars($_POST['invoiceDate']);
    $invoiceNumber = htmlspecialchars($_POST['invoiceNumber']);
    $processedByDate = htmlspecialchars($_POST['processedByDate']);
    $createdByID = $_SESSION['user_id']; // Logged-in user ID

    try {
        $conn->beginTransaction();

        // Use prepared statements to prevent SQL injection
        $sql = "INSERT INTO StockIn (Supplier, InvoiceDate, InvoiceNumber, ProcessedByDate, CreatedByID) 
                VALUES (:supplier, :invoiceDate, :invoiceNumber, :processedByDate, :createdByID)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':supplier', $supplier);
        $stmt->bindParam(':invoiceDate', $invoiceDate);
        $stmt->bindParam(':invoiceNumber', $invoiceNumber);
        $stmt->bindParam(':processedByDate', $processedByDate);
        $stmt->bindParam(':createdByID', $createdByID);

        if ($stmt->execute()) {
            // Log Action in AuditLog
            $newStockInID = $conn->lastInsertId();
            $action = "Added StockIn: Supplier - $supplier, Invoice Date - $invoiceDate, Invoice Number - $invoiceNumber (ID: $newStockInID)";
            $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bindParam(':userID', $createdByID);
            $logStmt->bindParam(':action', $action);
            $logStmt->execute();

            $conn->commit();
            header("Location: stockin.php");
            exit();
        } else {
            throw new Exception("Error adding StockIn: " . implode(" ", $stmt->errorInfo()));
        }
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// Handle Update StockIn
if (isset($_POST['update'])) {
    $stockInID = intval($_POST['stockInID']);
    $supplier = htmlspecialchars($_POST['supplier']);
    $invoiceDate = htmlspecialchars($_POST['invoiceDate']);
    $invoiceNumber = htmlspecialchars($_POST['invoiceNumber']);
    $processedByDate = htmlspecialchars($_POST['processedByDate']);
    $updatedByID = $_SESSION['user_id']; // Logged-in user ID

    try {
        $conn->beginTransaction();

        // Use prepared statements to prevent SQL injection
        $sql = "UPDATE StockIn SET Supplier=:supplier, InvoiceDate=:invoiceDate, InvoiceNumber=:invoiceNumber, 
                ProcessedByDate=:processedByDate WHERE StockInID=:stockInID";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':supplier', $supplier);
        $stmt->bindParam(':invoiceDate', $invoiceDate);
        $stmt->bindParam(':invoiceNumber', $invoiceNumber);
        $stmt->bindParam(':processedByDate', $processedByDate);
        $stmt->bindParam(':stockInID', $stockInID);

        if ($stmt->execute()) {
            // Log Action in AuditLog
            $action = "Updated StockIn: Supplier - $supplier, Invoice Date - $invoiceDate, Invoice Number - $invoiceNumber (ID: $stockInID)";
            $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bindParam(':userID', $updatedByID);
            $logStmt->bindParam(':action', $action);
            $logStmt->execute();

            $conn->commit();
            header("Location: stockin.php");
            exit();
        } else {
            throw new Exception("Error updating StockIn: " . implode(" ", $stmt->errorInfo()));
        }
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// Handle Delete StockIn (Return Stock)
if (isset($_GET['delete'])) {
    $stockInID = intval($_GET['delete']);
    $deletedByID = $_SESSION['user_id']; // Logged-in user ID

    try {
        $conn->beginTransaction();

        // Fetch StockIn details before deletion for logging
        $fetchSql = "SELECT Supplier, InvoiceDate, InvoiceNumber FROM StockIn WHERE StockInID=:stockInID";
        $fetchStmt = $conn->prepare($fetchSql);
        $fetchStmt->bindParam(':stockInID', $stockInID);
        if ($fetchStmt->execute()) {
            $stockIn = $fetchStmt->fetch(PDO::FETCH_ASSOC);
            if ($stockIn) {
                $supplier = $stockIn['Supplier'];
                $invoiceDate = $stockIn['InvoiceDate'];
                $invoiceNumber = $stockIn['InvoiceNumber'];

                // Fetch StockInDetails to update product quantities
                $detailsSql = "SELECT ProductID, Quantity FROM StockInDetails WHERE StockInID=:stockInID";
                $detailsStmt = $conn->prepare($detailsSql);
                $detailsStmt->bindParam(':stockInID', $stockInID);
                $detailsStmt->execute();
                $details = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($details as $detail) {
                    $productID = $detail['ProductID'];
                    $quantity = $detail['Quantity'];

                    // Update product quantity in stock
                    $updateProductSql = "UPDATE Product SET QuantityInStock = QuantityInStock - :quantity WHERE ProductID = :productID";
                    $updateProductStmt = $conn->prepare($updateProductSql);
                    $updateProductStmt->bindParam(':quantity', $quantity);
                    $updateProductStmt->bindParam(':productID', $productID);
                    $updateProductStmt->execute();
                }

                // Delete StockInDetails
                $deleteDetailsSql = "DELETE FROM StockInDetails WHERE StockInID=:stockInID";
                $deleteDetailsStmt = $conn->prepare($deleteDetailsSql);
                $deleteDetailsStmt->bindParam(':stockInID', $stockInID);
                $deleteDetailsStmt->execute();

                // Delete StockIn
                $deleteStockInSql = "DELETE FROM StockIn WHERE StockInID=:stockInID";
                $deleteStockInStmt = $conn->prepare($deleteStockInSql);
                $deleteStockInStmt->bindParam(':stockInID', $stockInID);
                $deleteStockInStmt->execute();

                // Log Action in AuditLog
                $action = "Returned StockIn: Supplier - $supplier, Invoice Date - $invoiceDate, Invoice Number - $invoiceNumber (ID: $stockInID)";
                $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
                $logStmt = $conn->prepare($logSql);
                $logStmt->bindParam(':userID', $deletedByID);
                $logStmt->bindParam(':action', $action);
                $logStmt->execute();

                $conn->commit();
                header("Location: stockin.php");
                exit();
            } else {
                throw new Exception("Error: StockIn record not found.");
            }
        } else {
            throw new Exception("Error executing query: " . implode(" ", $fetchStmt->errorInfo()));
        }
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// Calculate Total Cost for a StockIn record
function calculateTotalCost($conn, $stockInID) {
    $sql = "SELECT SUM(Quantity * UnitCost) AS TotalCost FROM StockInDetails WHERE StockInID = :stockInID";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':stockInID', $stockInID);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['TotalCost'] ?? 0;
}

// Update Total Cost in StockIn table
function updateTotalCost($conn, $stockInID) {
    $totalCost = calculateTotalCost($conn, $stockInID);
    $sql = "UPDATE StockIn SET TotalCost = :totalCost WHERE StockInID = :stockInID";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':totalCost', $totalCost);
    $stmt->bindParam(':stockInID', $stockInID);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockIn Management - Pet Zoofari IMS</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Your existing CSS styles */
        /* Your existing CSS styles */
        /* Your existing CSS styles */
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
        <h2>🦓 StockIn Management - Pet Zoofari IMS</h2>
        <button class="btn btn-secondary theme-toggle" onclick="toggleDarkMode()">🌙 Toggle Dark Mode</button>

        <!-- Search Form -->
        <form action="stockin.php" method="GET" class="form-inline mb-4">
            <input type="text" name="search" placeholder="Search by supplier or invoice number" class="form-control mr-2">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="dashboard_admin.php" class="btn btn-secondary ml-2">⬅️ Back to Admin Dashboard</a>
        </form>


        

        <!-- Add New StockIn Form -->
        <form action="stockin.php" method="POST" class="mb-4">
            <h4>Add New StockIn</h4>
            <input type="text" name="supplier" placeholder="Supplier" class="form-control mb-2" required>
            <input type="date" name="invoiceDate" placeholder="Invoice Date" class="form-control mb-2" required>
            <input type="text" name="invoiceNumber" placeholder="Invoice Number" class="form-control mb-2" required>
            <input type="date" name="processedByDate" placeholder="Processed By Date" class="form-control mb-2" required>
            <button type="submit" name="add" class="btn btn-success">Add StockIn</button>
        </form>

        <!-- Display StockIn Table -->
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>🆔 StockIn ID</th>
                    <th>🏢 Supplier</th>
                    <th>📅 Invoice Date</th>
                    <th>🧾 Invoice Number</th>
                    <th>📅 Processed By Date</th>
                    <th>💰 Total Cost</th>
                    <th>👤 Created By</th>
                    <th>🛠️ Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

                $sql = "SELECT s.StockInID, s.Supplier, s.InvoiceDate, s.InvoiceNumber, s.ProcessedByDate, 
                        s.TotalCost, u.Username 
                        FROM StockIn s 
                        LEFT JOIN User u ON s.CreatedByID = u.UserID";
                if (!empty($search)) {
                    $sql .= " WHERE s.Supplier LIKE :search OR s.InvoiceNumber LIKE :search";
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
                        // Update total cost before displaying
                        updateTotalCost($conn, $row['StockInID']);
                        
                        echo "<tr>
                            <td>{$row['StockInID']}</td>
                            <td>{$row['Supplier']}</td>
                            <td>{$row['InvoiceDate']}</td>
                            <td>{$row['InvoiceNumber']}</td>
                            <td>{$row['ProcessedByDate']}</td>
                            <td>₱" . number_format($row['TotalCost'], 2) . "</td>
                            <td>{$row['Username']}</td>
                            <td>
                                <button type='button' class='btn btn-info btn-sm' onclick='showDetails({$row['StockInID']})'>Details</button>
                                
                                <a href='stockin.php?delete={$row['StockInID']}' class='btn btn-danger btn-sm' onclick='return confirmDelete()'>Return Stock</a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No stock intake records found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Edit Form -->
        <?php
        if (isset($_GET['edit'])) {
            $stockInID = intval($_GET['edit']);
            $sql = "SELECT * FROM StockIn WHERE StockInID=:stockInID";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':stockInID', $stockInID);
            if ($stmt->execute()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    ?>
                    <form action="stockin.php" method="POST" class="mb-4">
                        <h4>Edit StockIn</h4>
                        <input type="hidden" name="stockInID" value="<?= $row['StockInID'] ?>">
                        <input type="text" name="supplier" value="<?= htmlspecialchars($row['Supplier']) ?>" class="form-control mb-2" required>
                        <input type="date" name="invoiceDate" value="<?= $row['InvoiceDate'] ?>" class="form-control mb-2" required>
                        <input type="text" name="invoiceNumber" value="<?= htmlspecialchars($row['InvoiceNumber']) ?>" class="form-control mb-2" required>
                        <input type="date" name="processedByDate" value="<?= $row['ProcessedByDate'] ?>" class="form-control mb-2" required>
                        <button type="submit" name="update" class="btn btn-primary">Update StockIn</button>
                        <a href="stockin.php" class="btn btn-secondary">Cancel</a>
                    </form>
                    <?php
                } else {
                    echo "<p>Error: StockIn record not found.</p>";
                }
            } else {
                echo "<p>Error executing query: " . implode(" ", $stmt->errorInfo()) . "</p>";
            }
        }
        ?>
        
        <!-- StockIn Details Modal -->
        <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailsModalLabel">Stock In Details</h5>
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
            return confirm('Are you sure you want to return this stock intake record?');
        }

        function confirmReturn() {
            return confirm('Are you sure you want to return this stock to the warehouse?');
        }
        
        function showDetails(stockInID) {
            // Load details via AJAX
            $.ajax({
                url: 'get_stockin_details.php',
                type: 'GET',
                data: { stockInID: stockInID },
                success: function(response) {
                    $('#detailsModalBody').html(response);
                    $('#detailsModal').modal('show');
                },
                error: function() {
                    alert('Error loading stock in details.');
                }
            });
        }
        
    </script>
</body>
</html>