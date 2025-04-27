<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'db_connect.php';

if (isset($_GET['id']) && isset($_GET['stockInID'])) {
    $stockInDetailID = intval($_GET['id']);
    $stockInID = intval($_GET['stockInID']);
    $userID = $_SESSION['user_id'];

    try {
        $conn->beginTransaction();

        // Fetch detail to get product ID and quantity
        $fetchDetailSql = "SELECT ProductID, Quantity FROM StockInDetails WHERE StockInDetailID = :stockInDetailID";
        $fetchDetailStmt = $conn->prepare($fetchDetailSql);
        $fetchDetailStmt->bindParam(':stockInDetailID', $stockInDetailID);
        $fetchDetailStmt->execute();
        $detail = $fetchDetailStmt->fetch(PDO::FETCH_ASSOC);

        if ($detail) {
            $productID = $detail['ProductID'];
            $quantity = $detail['Quantity'];

            // Delete the detail
            $deleteSql = "DELETE FROM StockInDetails WHERE StockInDetailID = :stockInDetailID";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bindParam(':stockInDetailID', $stockInDetailID);
            $deleteStmt->execute();

            // Update product quantity in stock
            $updateProductSql = "UPDATE Product SET QuantityInStock = QuantityInStock - :quantity
                                 WHERE ProductID = :productID";
            $updateProductStmt = $conn->prepare($updateProductSql);
            $updateProductStmt->bindParam(':quantity', $quantity);
            $updateProductStmt->bindParam(':productID', $productID);
            $updateProductStmt->execute();

            // Update total cost in StockIn table
            $updateTotalSql = "UPDATE StockIn SET TotalCost = (
                              SELECT SUM(Quantity * UnitCost)
                              FROM StockInDetails
                              WHERE StockInID = :stockInID)
                              WHERE StockInID = :stockInID";
            $updateTotalStmt = $conn->prepare($updateTotalSql);
            $updateTotalStmt->bindParam(':stockInID', $stockInID);
            $updateTotalStmt->execute();

            // Log action
            $fetchProductSql = "SELECT Name FROM Product WHERE ProductID = :productID";
            $fetchProductStmt = $conn->prepare($fetchProductSql);
            $fetchProductStmt->bindParam(':productID', $productID);
            $fetchProductStmt->execute();
            $productName = $fetchProductStmt->fetchColumn();

            $action = "Removed product '$productName' from StockIn #$stockInID - Quantity: $quantity";
            $logSql = "INSERT INTO AuditLog (UserID, Action) VALUES (:userID, :action)";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bindParam(':userID', $userID);
            $logStmt->bindParam(':action', $action);
            $logStmt->execute();

            // Commit the transaction if all operations succeed
            $conn->commit();

            // Redirect or show success message
            header('Location: stock_in_details.php?id=' . $stockInID . '&success=1');
            exit();
        } else {
            echo "Detail not found.";
            exit();
        }
    } catch (PDOException $e) {
        // Rollback the transaction if any operation fails
        $conn->rollBack();

        // Log the error
        error_log("Stock-in detail deletion failed: " . $e->getMessage());

        // Redirect with error or show error message
        header('Location: stock_in_details.php?id=' . $stockInID . '&error=1');
        exit();
    }
} else {
    echo "Invalid request.";
    exit();
}
?>