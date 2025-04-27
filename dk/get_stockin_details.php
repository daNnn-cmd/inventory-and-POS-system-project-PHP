<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized access";
    exit();
}

require 'db_connect.php';

if (isset($_GET['stockInID'])) {
    $stockInID = $_GET['stockInID'];
    
    // Get StockIn information
    $stockInSql = "SELECT * FROM StockIn WHERE StockInID = :stockInID";
    $stockInStmt = $conn->prepare($stockInSql);
    $stockInStmt->bindParam(':stockInID', $stockInID);
    $stockInStmt->execute();
    $stockIn = $stockInStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stockIn) {
        echo "Stock In record not found.";
        exit();
    }
    
    // Get StockIn details with product information
    $detailsSql = "SELECT sd.StockInDetailID, sd.Quantity, sd.UnitCost, p.Name AS ProductName, p.Brand 
                  FROM StockInDetails sd 
                  JOIN Product p ON sd.ProductID = p.ProductID 
                  WHERE sd.StockInID = :stockInID";
    $detailsStmt = $conn->prepare($detailsSql);
    $detailsStmt->bindParam(':stockInID', $stockInID);
    $detailsStmt->execute();
    
    // Display stock in information
    echo "<div class='stock-info mb-3'>
            <h4>Stock In Information</h4>
            <p><strong>Supplier:</strong> {$stockIn['Supplier']}</p>
            <p><strong>Invoice Date:</strong> {$stockIn['InvoiceDate']}</p>
            <p><strong>Invoice Number:</strong> {$stockIn['InvoiceNumber']}</p>
            <p><strong>Processed By Date:</strong> {$stockIn['ProcessedByDate']}</p>
        </div>";
    
    // Form to add new details
    echo "<div class='add-details mb-4'>
            <h5>Add Product to this Stock In</h5>
            <form action='add_stockin_detail.php' method='POST' class='form-inline'>
                <input type='hidden' name='stockInID' value='{$stockInID}'>
                <select name='productID' class='form-control mr-2' required>
                    <option value=''>Select Product</option>";
                    
    // Get all products for dropdown
    $productsSql = "SELECT ProductID, Name, Brand FROM Product ORDER BY Name";
    $productsStmt = $conn->query($productsSql);
    while ($product = $productsStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<option value='{$product['ProductID']}'>{$product['Name']} ({$product['Brand']})</option>";
    }
    
    echo "      </select>
                <input type='number' name='quantity' placeholder='Quantity' class='form-control mr-2' required>
                <input type='number' step='0.01' name='unitCost' placeholder='Unit Cost' class='form-control mr-2' required>
                <button type='submit' class='btn btn-success'>Add Product</button>
            </form>
        </div>";
    
    // Display details table
    echo "<h5>Product Details</h5>";
    
    if ($detailsStmt->rowCount() > 0) {
        echo "<table class='table table-striped'>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Cost</th>
                        <th>Subtotal</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>";
        
        $totalCost = 0;
        while ($detail = $detailsStmt->fetch(PDO::FETCH_ASSOC)) {
            $subtotal = $detail['Quantity'] * $detail['UnitCost'];
            $totalCost += $subtotal;
            
            echo "<tr>
                    <td>{$detail['ProductName']} ({$detail['Brand']})</td>
                    <td>{$detail['Quantity']}</td>
                    <td>₱" . number_format($detail['UnitCost'], 2) . "</td>
                    <td>₱" . number_format($subtotal, 2) . "</td>
                    <td>
                        <a href='delete_stockin_detail.php?id={$detail['StockInDetailID']}&stockInID={$stockInID}' 
                           class='btn btn-danger btn-sm' 
                           onclick='return confirm(\"Are you sure you want to remove this product?\")'>
                            Remove
                        </a>
                    </td>
                </tr>";
        }
        
        echo "<tr class='table-info'>
                <td colspan='3'><strong>Total Cost</strong></td>
                <td colspan='2'><strong>₱" . number_format($totalCost, 2) . "</strong></td>
              </tr>";
        
        echo "</tbody></table>";
    } else {
        echo "<p>No products have been added to this stock in record yet.</p>";
    }
} else {
    echo "Stock In ID is required.";
}
?>