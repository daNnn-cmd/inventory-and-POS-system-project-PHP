<?php
session_start();

// Redirect to login if user is not logged in or is not an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php'; // Ensure this file contains the PDO database connection logic

if (isset($_GET['stockInID'])) {
    $stockInID = $_GET['stockInID'];
    $userID = $_SESSION['user_id'];

    try {
        $conn->beginTransaction();

        // Fetch the stock in details
        $fetchDetailsSql = "SELECT ProductID, Quantity FROM StockInDetails WHERE StockInID = :stockInID";
        $fetchDetailsStmt = $conn->prepare($fetchDetailsSql);
        $fetchDetailsStmt->bindParam(':stockInID', $stockInID);
        $fetchDetailsStmt->execute();
        $details = $fetchDetailsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Return each product to the warehouse
        foreach ($details as $detail) {
            $productID = $detail['ProductID'];
            $quantity = $detail['Quantity'];

            // Update the product quantity in stock
                       $updateProductSql = "UPDATE Product SET QuantityInStock = QuantityInStock - :quantity WHERE ProductID = :productID";
            $updateProductStmt = $conn->prepare($updateProductSql);
            $updateProductStmt->bindParam(':quantity', $quantity);
            $updateProductStmt->bindParam(':productID', $productID);
            $updateProductStmt->execute();
        }

        // Log the return action in the audit log
        $action = "Returned stock from StockIn ID: $stockInID";
        $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':userID', $userID);
        $logStmt->bindParam(':action', $action);
        $logStmt->execute();

        // Commit the transaction
        $conn->commit();

        // Redirect back to the stock in page with a success message
        header("Location: stockin.php?return_success=1");
        exit();
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "StockIn ID is required.";
}
?>