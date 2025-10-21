<?php
// cart.php - Cart Management with Payment Redirect
session_start();

// Database Configuration
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "cake_shop";
    private $conn;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8mb4");
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Cart Management Class
class Cart {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->initializeSessionCart();
    }
    
    private function initializeSessionCart() {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }
    
    // Add item to cart
    public function addItem($cakeId, $cakeName, $price, $quantity = 1) {
        $cartKey = 'cake_' . $cakeId;
        
        if (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$cartKey] = [
                'id' => $cakeId,
                'name' => $cakeName,
                'price' => $price,
                'quantity' => $quantity
            ];
        }
        
        return true;
    }
    
    // Get all cart items
    public function getItems() {
        return $_SESSION['cart'];
    }
    
    // Update item quantity
    public function updateQuantity($cakeId, $quantity) {
        $cartKey = 'cake_' . $cakeId;
        
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$cartKey]);
        } else {
            if (isset($_SESSION['cart'][$cartKey])) {
                $_SESSION['cart'][$cartKey]['quantity'] = $quantity;
            }
        }
        
        return true;
    }
    
    // Remove item from cart
    public function removeItem($cakeId) {
        $cartKey = 'cake_' . $cakeId;
        unset($_SESSION['cart'][$cartKey]);
        return true;
    }
    
    // Get cart totals
    public function getTotals() {
        $totalItems = count($_SESSION['cart']);
        $totalQuantity = 0;
        $cartTotal = 0;
        
        foreach ($_SESSION['cart'] as $item) {
            $totalQuantity += $item['quantity'];
            $cartTotal += $item['price'] * $item['quantity'];
        }
        
        return [
            'total_items' => $totalItems,
            'total_quantity' => $totalQuantity,
            'cart_total' => $cartTotal
        ];
    }
    
    // Clear cart after purchase
    public function clearCart() {
        $_SESSION['cart'] = [];
        return true;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $cart = new Cart();
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $cakeId = intval($_POST['id']);
            $cakeName = $_POST['name'];
            $price = floatval($_POST['price']);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            $cart->addItem($cakeId, $cakeName, $price, $quantity);
            echo json_encode(['success' => true, 'message' => 'Item added to cart']);
            break;
            
        case 'update':
            $cakeId = intval($_POST['id']);
            $quantity = intval($_POST['quantity']);
            
            $cart->updateQuantity($cakeId, $quantity);
            echo json_encode(['success' => true]);
            break;
            
        case 'remove':
            $cakeId = intval($_POST['id']);
            
            $cart->removeItem($cakeId);
            echo json_encode(['success' => true]);
            break;
            
        case 'get':
            $items = $cart->getItems();
            $totals = $cart->getTotals();
            
            echo json_encode([
                'success' => true,
                'items' => $items,
                'totals' => $totals
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Handle GET request for adding item from product page
if (isset($_GET['id']) && isset($_GET['name']) && isset($_GET['price'])) {
    $cart = new Cart();
    $cart->addItem(
        intval($_GET['id']),
        $_GET['name'],
        floatval($_GET['price']),
        1
    );
}

// Display cart page
$cart = new Cart();
$items = $cart->getItems();
$totals = $cart->getTotals();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Sweet Delights</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            color: #764ba2;
            font-size: 32px;
        }

        .back-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s;
        }

        .back-link:hover {
            transform: translateY(-2px);
        }

        .cart-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .cart-empty {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .cart-empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .cart-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 20px;
            color: #333;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .item-price {
            color: #764ba2;
            font-size: 18px;
            font-weight: bold;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .qty-btn {
            width: 35px;
            height: 35px;
            border: 2px solid #764ba2;
            background: white;
            color: #764ba2;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
        }

        .qty-btn:hover {
            background: #764ba2;
            color: white;
        }

        .qty-display {
            font-size: 18px;
            font-weight: bold;
            min-width: 30px;
            text-align: center;
        }

        .remove-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .remove-btn:hover {
            background: #ff5252;
        }

        .cart-summary {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .summary-row.total {
            font-size: 22px;
            font-weight: bold;
            color: #764ba2;
            border-top: 2px solid #ddd;
            padding-top: 15px;
        }

        .checkout-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.3s;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🛒 Shopping Cart</h1>
            <a href="index.php" class="back-link">← Continue Shopping</a>
        </header>

        <div class="cart-container">
            <div id="cartItems">
                <?php if (empty($items)): ?>
                    <div class="cart-empty">
                        <div class="cart-empty-icon">🛒</div>
                        <h2>Your cart is empty</h2>
                        <p>Add some delicious cakes to get started!</p>
                        <br>
                        <a href="index.php" class="back-link">Browse Cakes</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $key => $item): ?>
                        <div class="cart-item" data-id="<?= $item['id'] ?>">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="item-price">₹<?= number_format($item['price'], 2) ?> per kg</div>
                            </div>
                            <div class="quantity-controls">
                                <button class="qty-btn" onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>)">-</button>
                                <div class="qty-display"><?= $item['quantity'] ?></div>
                                <button class="qty-btn" onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>)">+</button>
                            </div>
                            <div style="text-align: right; min-width: 120px;">
                                <div style="font-size: 20px; font-weight: bold; color: #764ba2; margin-bottom: 10px;">
                                    ₹<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                </div>
                                <button class="remove-btn" onclick="removeItem(<?= $item['id'] ?>)">Remove</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($items)): ?>
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Items (<?= $totals['total_items'] ?>)</span>
                        <span><?= $totals['total_quantity'] ?> cakes</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Amount</span>
                        <span>₹<?= number_format($totals['cart_total'], 2) ?></span>
                    </div>
                    
                    <button class="checkout-btn" onclick="proceedToCheckout()">
                        🔒 Proceed to Checkout
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateQuantity(cakeId, newQuantity) {
            if (newQuantity < 1) {
                removeItem(cakeId);
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'update');
            formData.append('id', cakeId);
            formData.append('quantity', newQuantity);
            
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        function removeItem(cakeId) {
            if (!confirm('Remove this item from cart?')) return;
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'remove');
            formData.append('id', cakeId);
            
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        function proceedToCheckout() {
            // Redirect to payment page which will read all items from session cart
            window.location.href = 'payment.php';
        }
    </script>
</body>
</html>