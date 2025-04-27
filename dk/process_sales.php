<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Cashier') {
    header('Location: login.php');
    exit();
}

require 'db_connect.php';

$receipt_html = ''; // Variable to store receipt HTML
$error_message = '';
$success_message = '';

// Initialize product count (used for form submission)
$product_count = isset($_POST['product_count']) ? $_POST['product_count'] : 1;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $customer_id = $_POST['customer_id'];
    $payment_method = $_POST['payment_method'];
    $created_by = $_SESSION['user_id'];
    
    // Collect products from form
    $products = [];
    for ($i = 0; $i < $product_count; $i++) {
        if (isset($_POST['product_id'][$i]) && !empty($_POST['product_id'][$i])) {
            $products[] = [
                'product_id' => $_POST['product_id'][$i],
                'quantity' => $_POST['quantity'][$i]
            ];
        }
    }
    
    // Start transaction
    $conn->beginTransaction();

    try {
        // Fetch customer details
        $stmt = $conn->prepare("SELECT FirstName, LastName FROM Customer WHERE CustomerID = :customer_id");
        $stmt->execute(['customer_id' => $customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        $customer_name = $customer['FirstName'] . ' ' . $customer['LastName'];

        // Insert order
        $stmt = $conn->prepare("INSERT INTO `Order` (CustomerID, TotalAmount, CreatedByID) VALUES (:customer_id, :total_amount, :created_by)");
        $stmt->execute([
            'customer_id' => $customer_id,
            'total_amount' => 0, // Will be updated
            'created_by' => $created_by
        ]);
        $order_id = $conn->lastInsertId();

        $total_amount = 0;
        $receipt_items = []; // Array to store receipt items

        // Insert order details and update product quantities
        foreach ($products as $product) {
            $product_id = $product['product_id'];
            $quantity = $product['quantity'];

            // Fetch product details
            $stmt = $conn->prepare("SELECT Name, Price, QuantityInStock FROM Product WHERE ProductID = :product_id FOR UPDATE");
            $stmt->execute(['product_id' => $product_id]);
            $product_details = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product_details['QuantityInStock'] < $quantity) {
                throw new Exception("Insufficient stock for product ID $product_id");
            }

            $subtotal = $product_details['Price'] * $quantity;
            $total_amount += $subtotal;

            // Store receipt item
            $receipt_items[] = [
                'name' => $product_details['Name'],
                'quantity' => $quantity,
                'price' => $product_details['Price'],
                'subtotal' => $subtotal
            ];

            // Insert order detail
            $stmt = $conn->prepare("INSERT INTO OrderDetails (OrderID, ProductID, Quantity, UnitPrice, Subtotal) VALUES (:order_id, :product_id, :quantity, :unit_price, :subtotal)");
            $stmt->execute([
                'order_id' => $order_id,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'unit_price' => $product_details['Price'],
                'subtotal' => $subtotal
            ]);

            // Update product quantity
            $stmt = $conn->prepare("UPDATE Product SET QuantityInStock = QuantityInStock - :quantity WHERE ProductID = :product_id");
            $stmt->execute([
                'quantity' => $quantity,
                'product_id' => $product_id
            ]);
        }

        // Update order total amount
        $stmt = $conn->prepare("UPDATE `Order` SET TotalAmount = :total_amount WHERE OrderID = :order_id");
        $stmt->execute([
            'total_amount' => $total_amount,
            'order_id' => $order_id
        ]);

        // Insert payment
        $stmt = $conn->prepare("INSERT INTO Payment (OrderID, Amount, PaymentMethod, PaymentStatus) VALUES (:order_id, :amount, :payment_method, 'Completed')");
        $stmt->execute([
            'order_id' => $order_id,
            'amount' => $total_amount,
            'payment_method' => $payment_method
        ]);

        // Log the action in AuditLog
        $action = "Processed sale and payment for Order ID: $order_id";
        $stmt = $conn->prepare("INSERT INTO AuditLog (UserID, Action) VALUES (:user_id, :action)");
        $stmt->execute([
            'user_id' => $created_by,
            'action' => $action
        ]);

        // Commit transaction
        $conn->commit();

        // Generate receipt HTML
        $receipt_html = generateReceipt($order_id, $customer_name, $receipt_items, $total_amount, $payment_method);
        $success_message = "Sale and payment processed successfully!";
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollBack();
        $error_message = "Error: " . $e->getMessage();
    }
} 
// Handle add product action
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $product_count++;
}
// Handle remove product action
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_product'])) {
    $remove_index = $_POST['remove_index'];
    
    // Rebuild product array without the removed item
    $new_product_ids = [];
    $new_quantities = [];
    
    for ($i = 0; $i < $product_count; $i++) {
        if ($i != $remove_index) {
            $new_product_ids[] = $_POST['product_id'][$i] ?? '';
            $new_quantities[] = $_POST['quantity'][$i] ?? 1;
        }
    }
    
    $_POST['product_id'] = $new_product_ids;
    $_POST['quantity'] = $new_quantities;
    $product_count--;
}
// Handle recalculate action
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['recalculate'])) {
    // The form was submitted for recalculation - values will be retained
}

// Function to generate receipt HTML
function generateReceipt($order_id, $customer_name, $items, $total_amount, $payment_method) {
    $receipt = "<div class='receipt'>";
    $receipt .= "<h2>Pet Zoofari 🐾</h2>";
    $receipt .= "<h3>Order Receipt</h3>";
    $receipt .= "<p><strong>Order ID:</strong> $order_id</p>";
    $receipt .= "<p><strong>Customer:</strong> $customer_name</p>";
    $receipt .= "<p><strong>Date:</strong> " . date("Y-m-d H:i:s") . "</p>";
    $receipt .= "<table border='1' cellpadding='10' cellspacing='0'>";
    $receipt .= "<tr><th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr>";
    foreach ($items as $item) {
        $receipt .= "<tr>";
        $receipt .= "<td>{$item['name']}</td>";
        $receipt .= "<td>{$item['quantity']}</td>";
        $receipt .= "<td>₱" . number_format($item['price'], 2) . "</td>";
        $receipt .= "<td>₱" . number_format($item['subtotal'], 2) . "</td>";
        $receipt .= "</tr>";
    }
    $receipt .= "<tr><td colspan='3'><strong>Total Amount</strong></td><td><strong>₱" . number_format($total_amount, 2) . "</strong></td></tr>";
    $receipt .= "</table>";
    $receipt .= "<p><strong>Payment Method:</strong> $payment_method</p>";
    $receipt .= "<p>Thank you for shopping with us! 🐾</p>";
    $receipt .= "</div>";
    return $receipt;
}

// Calculate totals for each product in the form
function calculateProductTotals($products, $quantities) {
    global $conn;
    
    $totals = [];
    $grand_total = 0;
    $items = [];
    
    // Prepare query to get product details efficiently
    $product_ids = array_filter($products);
    if (empty($product_ids)) {
        return ['items' => [], 'total' => 0];
    }
    
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $conn->prepare("SELECT ProductID, Name, Price FROM Product WHERE ProductID IN ($placeholders)");
    $stmt->execute($product_ids);
    $product_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $product_lookup = [];
    foreach ($product_data as $product) {
        $product_lookup[$product['ProductID']] = $product;
    }
    
    // Calculate totals for each selected product
    for ($i = 0; $i < count($products); $i++) {
        if (!empty($products[$i]) && isset($product_lookup[$products[$i]])) {
            $price = $product_lookup[$products[$i]]['Price'];
            $quantity = intval($quantities[$i]);
            $subtotal = $price * $quantity;
            $grand_total += $subtotal;
            
            $items[] = [
                'product_id' => $products[$i],
                'name' => $product_lookup[$products[$i]]['Name'],
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
        }
    }
    
    return [
        'items' => $items,
        'total' => $grand_total
    ];
}

// Fetch customers and products
$customers = $conn->query("SELECT * FROM Customer")->fetchAll(PDO::FETCH_ASSOC);
$products = $conn->query("SELECT * FROM Product")->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals if form has been submitted
$cart_items = [];
$grand_total = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    
    $calculation = calculateProductTotals($product_ids, $quantities);
    $cart_items = $calculation['items'];
    $grand_total = $calculation['total'];
}

// Get selected payment method
$selected_payment = $_POST['payment_method'] ?? 'Cash';
?>

<!DOCTYPE html>
<html>
<head>
    <title>POS System - Pet Zoofari</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Modern POS System Styles */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --accent-color: #f39c12;
            --dark-color: #34495e;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background-color: var(--dark-color);
            color: white;
            padding: 20px;
            box-shadow: var(--box-shadow);
            display: flex;
            flex-direction: column;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .header h1 {
            font-size: 24px;
            color: var(--dark-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .content-wrapper {
            display: flex;
            flex: 1;
            gap: 20px;
        }

        .product-selection {
            flex: 1;
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
        }

        .order-summary {
            width: 400px;
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            display: flex;
            flex-direction: column;
        }

        .section-title {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--dark-color);
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .customer-selection {
            margin-bottom: 20px;
        }

        select, input[type="number"], input[type="text"] {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 14px;
            margin-bottom: 10px;
        }

        select:focus, input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .product-item {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .product-item select {
            flex: 3;
        }

        .product-price {
            flex: 1;
            font-weight: bold;
            color: var(--dark-color);
        }

        .product-quantity {
            flex: 1;
        }

        .product-subtotal {
            flex: 1;
            font-weight: bold;
            color: var(--primary-color);
        }

        .remove-product {
            background-color: var(--danger-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .remove-product:hover {
            background-color: #c0392b;
        }

        .add-product-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 10px 15px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 15px;
            transition: background-color 0.2s;
        }

        .add-product-btn:hover {
            background-color: #27ae60;
        }

        .cart-items {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .cart-item-name {
            flex: 2;
            font-weight: bold;
        }

        .cart-item-details {
            flex: 1;
            text-align: right;
        }

        .order-totals {
            margin-top: auto;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .grand-total {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }

        .payment-section {
            margin-top: 20px;
        }

        .payment-options {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .payment-option {
            flex: 1;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: var(--border-radius);
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .payment-option:hover {
            border-color: var(--primary-color);
        }

        .payment-option.selected {
            border-color: var(--primary-color);
            background-color: rgba(52, 152, 219, 0.1);
        }

        .payment-option i {
            font-size: 24px;
            margin-bottom: 5px;
            color: var(--dark-color);
        }

        .process-payment-btn {
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .process-payment-btn:hover {
            background-color: #2980b9;
        }

        .process-payment-btn:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: var(--dark-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            margin-top: 20px;
            transition: background-color 0.2s;
        }

        .back-btn:hover {
            background-color: #2c3e50;
        }

        /* Receipt Styles */
        .receipt-container {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .receipt {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 380px;
            font-size: 14px;
        }

        .receipt h2, .receipt h3 {
            text-align: center;
            margin-bottom: 10px;
        }

        .receipt table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .receipt table th, .receipt table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .receipt table th {
            background-color: #f9f9f9;
        }

        .print-btn {
            padding: 10px 20px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            margin-top: 15px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }

        .print-btn:hover {
            background-color: #e67e22;
        }

        .success-message {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }

        .error-message {
            background-color: rgba(231, 76, 60, 0.2);
            color: #c0392b;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }

        .cart-placeholder {
            padding: 20px;
            text-align: center;
            color: #aaa;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            .receipt, .receipt * {
                visibility: visible;
            }
            .receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
            }
            .print-btn {
                display: none;
            }
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .content-wrapper {
                flex-direction: column;
            }
            .order-summary {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                padding: 15px;
            }
            .main-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-paw"></i> Pet Zoofari
            </div>
            <nav>
                <a href="dashboard_cashier.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </nav>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Process Sale</h1>
                <div class="user-info">
                    <i class="fas fa-user-circle" style="font-size: 24px;"></i>
                    <span>Cashier</span>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?= $success_message ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?= $error_message ?></div>
            <?php endif; ?>

            <form method="post" id="pos-form">
                <input type="hidden" name="product_count" value="<?= $product_count ?>">
                
                <div class="content-wrapper">
                    <div class="product-selection">
                        <h2 class="section-title">Order Details</h2>
                        
                        <div class="customer-selection">
                            <label for="customer_id">Select Customer:</label>
                            <select name="customer_id" id="customer_id" required>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['CustomerID'] ?>" <?= (isset($_POST['customer_id']) && $_POST['customer_id'] == $customer['CustomerID']) ? 'selected' : '' ?>>
                                    <?= $customer['FirstName'] . ' ' . $customer['LastName'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <h3 class="section-title">Products</h3>
                        <div id="product-items">
                            <?php for ($i = 0; $i < $product_count; $i++): ?>
                                <div class="product-item">
                                    <select name="product_id[]" class="product-select" required>
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $product): ?>
                                        <option value="<?= $product['ProductID'] ?>" 
                                            <?= (isset($_POST['product_id'][$i]) && $_POST['product_id'][$i] == $product['ProductID']) ? 'selected' : '' ?>>
                                            <?= $product['Name'] ?> - ₱<?= number_format($product['Price'], 2) ?> (<?= $product['QuantityInStock'] ?> in stock)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="product-price">
                                        <?php 
                                            if (isset($_POST['product_id'][$i]) && !empty($_POST['product_id'][$i])) {
                                                foreach ($products as $product) {
                                                    if ($product['ProductID'] == $_POST['product_id'][$i]) {
                                                        echo '₱' . number_format($product['Price'], 2);
                                                        break;
                                                    }
                                                }
                                            } else {
                                                echo '₱0.00';
                                            }
                                        ?>
                                    </div>
                                    <input type="number" name="quantity[]" class="product-quantity" min="1" value="<?= isset($_POST['quantity'][$i]) ? $_POST['quantity'][$i] : 1 ?>" required>
                                    <div class="product-subtotal">
                                        <?php 
                                            if (isset($_POST['product_id'][$i]) && !empty($_POST['product_id'][$i])) {
                                                foreach ($products as $product) {
                                                    if ($product['ProductID'] == $_POST['product_id'][$i]) {
                                                        $qty = isset($_POST['quantity'][$i]) ? $_POST['quantity'][$i] : 1;
                                                        echo '₱' . number_format($product['Price'] * $qty, 2);
                                                        break;
                                                    }
                                                }
                                            } else {
                                                echo '₱0.00';
                                            }
                                        ?>
                                    </div>
                                    <?php if ($i > 0 || $product_count > 1): ?>
                                        <button type="submit" name="remove_product" value="1" class="remove-product">
                                            <input type="hidden" name="remove_index" value="<?= $i ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="remove-product" style="visibility: hidden;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                        
                        <button type="submit" name="add_product" value="1" class="add-product-btn">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                        
                        <button type="submit" name="recalculate" value="1" class="add-product-btn" style="margin-top: 10px; background-color: var(--accent-color);">
                            <i class="fas fa-calculator"></i> Recalculate
                        </button>
                    </div>

                    <div class="order-summary">
                        <h2 class="section-title">Order Summary</h2>
                        
                        <div class="cart-items">
                            <?php if (empty($cart_items)): ?>
                                <div class="cart-placeholder">
                                    No items added yet.
                                </div>
                            <?php else: ?>
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="cart-item">
                                        <div class="cart-item-name"><?= $item['name'] ?> x<?= $item['quantity'] ?></div>
                                        <div class="cart-item-details">₱<?= number_format($item['subtotal'], 2) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-totals">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span id="subtotal">₱<?= number_format($grand_total, 2) ?></span>
                            </div>
                            <div class="total-row grand-total">
                                <span>Total:</span>
                                <span id="total">₱<?= number_format($grand_total, 2) ?></span>
                            </div>
                        </div>
                        
                        <div class="payment-section">
                            <h3 class="section-title">Payment Method</h3>
                            <div class="payment-options">
                                <div class="payment-option <?= $selected_payment == 'Cash' ? 'selected' : '' ?>" onclick="document.getElementById('payment_method').value='Cash';">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <div>Cash</div>
                                </div>
                                <div class="payment-option <?= $selected_payment == 'Card' ? 'selected' : '' ?>" onclick="
                                <div class="payment-option <?= $selected_payment == 'Card' ? 'selected' : '' ?>" onclick="document.getElementById('payment_method').value='Card';">
                                    <i class="fas fa-credit-card"></i>
                                    <div>Card</div>
                                </div>
                               
                            </div>
                            <input type="hidden" id="payment_method" name="payment_method" value="<?= $selected_payment ?>">
                            
                            <button type="submit" name="process_payment" value="1" class="process-payment-btn" <?= empty($cart_items) ? 'disabled' : '' ?>>
                                <i class="fas fa-check-circle"></i> Process Payment
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <?php if (!empty($receipt_html)): ?>
            <div class="receipt-container">
                <?= $receipt_html ?>
                <button class="print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        
        // Simple client-side handling for payment options
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.payment-option').forEach(el => {
                    el.classList.remove('selected');
                });
                // Add selected class to clicked option
                this.classList.add('selected');
            });
        });
    </script>

    <!-- JavaScript for Recalculation -->
<script>
    function recalculateTotals() {
        let subtotal = 0;
        const productItems = document.querySelectorAll('.product-item');

        productItems.forEach(item => {
            const price = parseFloat(item.querySelector('.product-price').textContent.replace('₱', ''));
            const quantity = parseFloat(item.querySelector('.product-quantity').value);
            const subtotalField = item.querySelector('.product-subtotal');

            if (!isNaN(price) && !isNaN(quantity)) {
                const productSubtotal = price * quantity;
                subtotalField.textContent = '₱' + productSubtotal.toFixed(2);
                subtotal += productSubtotal;
            } else {
                subtotalField.textContent = '₱0.00';
            }
        });

        document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
        document.getElementById('total').textContent = '₱' + subtotal.toFixed(2);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const productSelects = document.querySelectorAll('.product-select');
        const quantityInputs = document.querySelectorAll('.product-quantity');

        productSelects.forEach(select => {
            select.addEventListener('change', recalculateTotals);
        });

        quantityInputs.forEach(input => {
            input.addEventListener('input', recalculateTotals);
        });

        recalculateTotals();
    });
</script>

<!-- Recalculate Button -->
<button type="button" onclick="recalculateTotals()" class="add-product-btn" style="margin-top: 10px; background-color: var(--accent-color);">
    <i class="fas fa-calculator"></i> Recalculate
</button>
</body>
</html>