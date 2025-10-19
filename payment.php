<?php
// payment.php
session_start();

// Database Configuration Class
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
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Order Model Class
class Order {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    public function createOrder($userId, $cakeId, $cakeName, $price, $quantity, $totalAmount) {
        $userId = $this->conn->real_escape_string($userId);
        $cakeId = $this->conn->real_escape_string($cakeId);
        $cakeName = $this->conn->real_escape_string($cakeName);
        $price = $this->conn->real_escape_string($price);
        $quantity = $this->conn->real_escape_string($quantity);
        $totalAmount = $this->conn->real_escape_string($totalAmount);
        
        $query = "INSERT INTO orders (user_id, cake_id, cake_name, price, quantity, total_amount, order_date, status) 
                  VALUES ('$userId', '$cakeId', '$cakeName', '$price', '$quantity', '$totalAmount', NOW(), 'pending')";
        
        if ($this->conn->query($query)) {
            return $this->conn->insert_id;
        }
        return false;
    }
    
    public function updateOrderStatus($orderId, $status, $paymentId = null, $razorpayOrderId = null) {
        $orderId = $this->conn->real_escape_string($orderId);
        $status = $this->conn->real_escape_string($status);
        $paymentId = $this->conn->real_escape_string($paymentId);
        $razorpayOrderId = $this->conn->real_escape_string($razorpayOrderId);
        
        $query = "UPDATE orders SET status = '$status', payment_id = '$paymentId', 
                  razorpay_order_id = '$razorpayOrderId', payment_date = NOW() 
                  WHERE id = '$orderId'";
        
        return $this->conn->query($query);
    }
    
    public function getOrderById($orderId) {
        $orderId = $this->conn->real_escape_string($orderId);
        $query = "SELECT * FROM orders WHERE id = '$orderId'";
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }
    
    public function __destruct() {
        $this->db->closeConnection();
    }
}

// Cake Model Class
class Cake {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    public function getCakeById($id) {
        $id = $this->conn->real_escape_string($id);
        $query = "SELECT * FROM cakes WHERE id = '$id'";
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }
    
    public function __destruct() {
        $this->db->closeConnection();
    }
}

// User Authentication Class
class User {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getUserName() {
        return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
    }
    
    public function getUserEmail() {
        return isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
    }
    
    public function getUserPhone() {
        return isset($_SESSION['user_phone']) ? $_SESSION['user_phone'] : '';
    }
    
    public function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    }
    
    public function __destruct() {
        $this->db->closeConnection();
    }
}

// Payment Handler Class
class PaymentHandler {
    private $razorpayKey = "rzp_test_qz3vZymFK7JynA";
    private $razorpaySecret = "YOUR_SECRET_KEY"; // Add your secret key here
    
    public function getRazorpayKey() {
        return $this->razorpayKey;
    }
    
    public function generateOrderId() {
        return 'ORDER_' . time() . rand(1000, 9999);
    }
    
    public function verifyPayment($razorpayPaymentId, $razorpayOrderId, $razorpaySignature) {
        $generated_signature = hash_hmac('sha256', $razorpayOrderId . "|" . $razorpayPaymentId, $this->razorpaySecret);
        return ($generated_signature == $razorpaySignature);
    }
}

// Initialize Objects
$userModel = new User();
$cakeModel = new Cake();
$orderModel = new Order();
$paymentHandler = new PaymentHandler();

// Check if cake ID is provided
if (!isset($_GET['id']) || !isset($_GET['name']) || !isset($_GET['price'])) {
    header('Location: index.php');
    exit();
}

$cakeId = $_GET['id'];
$cakeName = $_GET['name'];
$cakePrice = $_GET['price'];

// Get cake details from database
$cake = $cakeModel->getCakeById($cakeId);
if (!$cake) {
    header('Location: index.php');
    exit();
}

// User details
$userName = $userModel->getUserName();
$userEmail = $userModel->getUserEmail();
$userPhone = $userModel->getUserPhone();
$userId = $userModel->getUserId();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Sweet Delights</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 25px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 40px;
        }

        .order-summary {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 20px;
            border: 2px solid #667eea;
        }

        .order-summary h2 {
            color: #764ba2;
            margin-bottom: 25px;
            font-size: 24px;
        }

        .product-info {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .product-info h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .product-info .price {
            color: #667eea;
            font-size: 28px;
            font-weight: bold;
            margin: 15px 0;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
        }

        .quantity-selector button {
            width: 40px;
            height: 40px;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .quantity-selector button:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .quantity-selector input {
            width: 60px;
            height: 40px;
            text-align: center;
            border: 2px solid #667eea;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
        }

        .price-breakdown {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin: 12px 0;
            font-size: 16px;
        }

        .price-row.total {
            border-top: 2px solid #667eea;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 20px;
            font-weight: bold;
            color: #764ba2;
        }

        .customer-details {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 20px;
            border: 2px solid #f093fb;
        }

        .customer-details h2 {
            color: #764ba2;
            margin-bottom: 25px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #555;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .payment-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            margin-top: 20px;
        }

        .payment-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.6);
        }

        .back-btn {
            display: inline-block;
            padding: 12px 30px;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            margin-top: 20px;
            transition: all 0.3s;
            font-weight: 600;
        }

        .back-btn:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateX(-5px);
        }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            color: #28a745;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }

        .success-message {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin: 20px 0;
            display: none;
        }

        .error-message {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin: 20px 0;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎂 Complete Your Order</h1>
            <p>Secure payment powered by Razorpay</p>
        </div>

        <div id="successMessage" class="success-message">
            <h2>✅ Payment Successful!</h2>
            <p>Your order has been placed successfully. Redirecting...</p>
        </div>

        <div id="errorMessage" class="error-message">
            <h2>❌ Payment Failed!</h2>
            <p id="errorText">Something went wrong. Please try again.</p>
        </div>

        <div class="content">
            <div class="order-summary">
                <h2>📋 Order Summary</h2>
                
                <div class="product-info">
                    <h3><?= htmlspecialchars($cakeName) ?></h3>
                    <div class="price">₹<?= number_format($cakePrice, 2) ?></div>
                    
                    <div class="quantity-selector">
                        <button onclick="decreaseQuantity()">-</button>
                        <input type="number" id="quantity" value="1" min="1" max="10" readonly>
                        <button onclick="increaseQuantity()">+</button>
                        <span style="color: #666;">Quantity</span>
                    </div>
                </div>

                <div class="price-breakdown">
                    <div class="price-row">
                        <span>Item Price:</span>
                        <span id="itemPrice">₹<?= number_format($cakePrice, 2) ?></span>
                    </div>
                    <div class="price-row">
                        <span>Quantity:</span>
                        <span id="quantityDisplay">1</span>
                    </div>
                    <div class="price-row">
                        <span>Delivery Charges:</span>
                        <span style="color: #28a745;">FREE</span>
                    </div>
                    <div class="price-row total">
                        <span>Total Amount:</span>
                        <span id="totalAmount">₹<?= number_format($cakePrice, 2) ?></span>
                    </div>
                </div>

                <a href="index.php" class="back-btn">← Back to Shop</a>
            </div>

            <div class="customer-details">
                <h2>👤 Customer Details</h2>
                
                <form id="paymentForm">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" id="customerName" value="<?= htmlspecialchars($userName) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" id="customerEmail" value="<?= htmlspecialchars($userEmail) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" id="customerPhone" value="<?= htmlspecialchars($userPhone) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Delivery Address *</label>
                        <textarea id="customerAddress" required placeholder="Enter your complete delivery address"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Special Instructions (Optional)</label>
                        <textarea id="specialInstructions" placeholder="Any special requests or delivery instructions"></textarea>
                    </div>

                    <button type="button" class="payment-btn" onclick="initiatePayment()">
                        🔒 Proceed to Secure Payment
                    </button>

                    <div class="secure-badge">
                        <span>🔐</span>
                        <span>100% Secure Payment via Razorpay</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const basePrice = <?= $cakePrice ?>;
        const cakeId = <?= $cakeId ?>;
        const cakeName = "<?= addslashes($cakeName) ?>";
        const userId = <?= $userId ?>;

        function updatePrice() {
            const quantity = parseInt(document.getElementById('quantity').value);
            const total = basePrice * quantity;
            
            document.getElementById('quantityDisplay').textContent = quantity;
            document.getElementById('totalAmount').textContent = '₹' + total.toFixed(2);
        }

        function increaseQuantity() {
            const input = document.getElementById('quantity');
            if (parseInt(input.value) < 10) {
                input.value = parseInt(input.value) + 1;
                updatePrice();
            }
        }

        function decreaseQuantity() {
            const input = document.getElementById('quantity');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                updatePrice();
            }
        }

        function initiatePayment() {
            const name = document.getElementById('customerName').value;
            const email = document.getElementById('customerEmail').value;
            const phone = document.getElementById('customerPhone').value;
            const address = document.getElementById('customerAddress').value;

            if (!name || !email || !phone || !address) {
                alert('Please fill all required fields!');
                return;
            }

            const quantity = parseInt(document.getElementById('quantity').value);
            const totalAmount = basePrice * quantity;

            var options = {
                "key": "<?= $paymentHandler->getRazorpayKey() ?>",
                "amount": totalAmount * 100, // Amount in paise
                "currency": "INR",
                "name": "Sweet Delights",
                "description": cakeName,
                "image": "https://cdn-icons-png.flaticon.com/512/3081/3081559.png",
                "handler": function (response) {
                    // Payment successful
                    saveOrder(response.razorpay_payment_id, response.razorpay_order_id, quantity, totalAmount);
                },
                "prefill": {
                    "name": name,
                    "email": email,
                    "contact": phone
                },
                "notes": {
                    "address": address,
                    "cake_id": cakeId,
                    "cake_name": cakeName
                },
                "theme": {
                    "color": "#667eea"
                },
                "modal": {
                    "ondismiss": function() {
                        showError("Payment cancelled by user");
                    }
                }
            };

            var rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response) {
                showError(response.error.description);
            });
            rzp.open();
        }

        function saveOrder(paymentId, orderId, quantity, totalAmount) {
            // Save order to database
            const formData = new FormData();
            formData.append('action', 'save_order');
            formData.append('user_id', userId);
            formData.append('cake_id', cakeId);
            formData.append('cake_name', cakeName);
            formData.append('price', basePrice);
            formData.append('quantity', quantity);
            formData.append('total_amount', totalAmount);
            formData.append('payment_id', paymentId);
            formData.append('razorpay_order_id', orderId);
            formData.append('customer_name', document.getElementById('customerName').value);
            formData.append('customer_email', document.getElementById('customerEmail').value);
            formData.append('customer_phone', document.getElementById('customerPhone').value);
            formData.append('customer_address', document.getElementById('customerAddress').value);
            formData.append('special_instructions', document.getElementById('specialInstructions').value);

            fetch('process_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess();
                    setTimeout(() => {
                        window.location.href = 'order_success.php?order_id=' + data.order_id;
                    }, 2000);
                } else {
                    showError("Failed to save order");
                }
            })
            .catch(error => {
                showError("Network error occurred");
            });
        }

        function showSuccess() {
            document.getElementById('successMessage').style.display = 'block';
            document.querySelector('.content').style.display = 'none';
        }

        function showError(message) {
            document.getElementById('errorText').textContent = message;
            document.getElementById('errorMessage').style.display = 'block';
            setTimeout(() => {
                document.getElementById('errorMessage').style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>