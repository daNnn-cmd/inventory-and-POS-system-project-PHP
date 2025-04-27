<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit(); 
}

require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stockInID = $_POST['stockInID'];
    $productID = $_POST['productID'];
    $quantity = $_POST['quantity'];
    $unitCost = $_POST['unitCost'];
    $userID = $_SESSION['user_id'];

    try {
        $conn->beginTransaction();
        
        // Insert into StockInDetails
        $sql = "INSERT INTO StockInDetails (StockInID, ProductID, Quantity, UnitCost)
                VALUES (:stockInID, :productID, :quantity, :unitCost)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':stockInID', $stockInID);
        $stmt->bindParam(':productID', $productID);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':unitCost', $unitCost);
        $stmt->execute();
        
        // Update product quantity in stock
        $updateProductSql = "UPDATE Product SET QuantityInStock = QuantityInStock + :quantity
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
        
        $action = "Added product '$productName' to StockIn #$stockInID - Quantity: $quantity, Unit Cost: $unitCost";
        $logSql = "INSERT INTO Auditlog (UserID, Action, ActionDate)
                  VALUES (:userID, :action, NOW())";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':userID', $userID);
        $logStmt->bindParam(':action', $action);
        $logStmt->execute();
        
        $conn->commit();
        error_log("Transaction committed successfully.");
        
        header('Location: stockin.php?id=' . $stockInID . '&success=1');
        exit();
        
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Transaction rolled back: " . $e->getMessage());
        
        header('Location: stockin.php?id=' . $stockInID . '&error=1');
        exit();
    }
}
?>